<?php

namespace App\Jobs;

use App\Services\AiClient;
use App\Services\DocumentText;
use App\Services\ResumeEntityExtractor;
use App\Services\SkillNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ParseResume implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(public int $resumeId)
    {
        $this->onQueue('high');
    }

    /** Helper: pick the first existing column name */
    protected function col(string ...$candidates): ?string
    {
        foreach ($candidates as $name) {
            if (Schema::hasColumn('resumes', $name)) return $name;
        }
        return null;
    }

    /** Detect absolute paths on *nix or Windows */
    protected function isAbsolute(string $p): bool
    {
        return str_starts_with($p, '/')
            || preg_match('/^[A-Za-z]:\\\\/', $p) === 1;
    }

    public function handle(AiClient $ai): void
    {
        $row = DB::table('resumes')->where('id', $this->resumeId)->first();
        if (!$row) return;

        $cols = [
            'file_path'   => $this->col('file_path', 'path', 'pathname', 'stored_path'),
            'storage'     => $this->col('storage_path'),
            'filename'    => $this->col('file_name', 'filename', 'name'),
            'mime'        => $this->col('mime', 'mime_type', 'content_type', 'mimetype'),
            'ai_status'   => $this->col('ai_status'),
            'ai_parsed'   => $this->col('ai_parsed'),
            'ai_error'    => $this->col('ai_error'),
            'updated_at'  => $this->col('updated_at'),
        ];

        // mark as processing
        $this->updateStatus($cols, 'processing', null, null);

        try {
            // Prefer storage_path, else file_path
            $candidate = null;

            if (!empty($cols['storage']) && !empty($row->{$cols['storage']})) {
                $candidate = (string) $row->{$cols['storage']};
            } elseif (!empty($cols['file_path']) && !empty($row->{$cols['file_path']})) {
                $candidate = (string) $row->{$cols['file_path']};
            }

            if (!$candidate) {
                throw new \RuntimeException('No stored path for resume (storage_path/file_path missing).');
            }

            // If relative, convert via public disk; if absolute, keep as-is
            $abs = $this->isAbsolute($candidate)
                ? $candidate
                : Storage::disk('public')->path($candidate);

            if (!is_file($abs)) {
                throw new \RuntimeException("Resume file not found at: {$abs}");
            }

            $mime = $cols['mime'] ? ($row->{$cols['mime']} ?? null) : null;
            $name = $cols['filename'] ? ($row->{$cols['filename']} ?? null) : null;

            $text = DocumentText::extract($abs, $mime, $name);

            if (!is_string($text) || mb_strlen(trim($text)) < 50) {
                throw new \RuntimeException('Could not extract enough readable text from the file.');
            }

            // light entities (rule-based, no AI)
            $entities = ResumeEntityExtractor::lightEntities($text);

            // AI parse
            $parsed = $ai->parseResumeText($text);
            if (!is_array($parsed)) {
                throw new \RuntimeException('AI returned an invalid structure.');
            }

            // Normalize AI output into consistent keys/arrays
            $normalized = $this->normalizeParsed($parsed, $text, $entities);

            $this->updateStatus(
                $cols,
                'done',
                json_encode($normalized, JSON_UNESCAPED_UNICODE),
                null
            );
        } catch (\Throwable $e) {
            $this->updateStatus(
                $cols,
                'error',
                null,
                mb_substr($e->getMessage(), 0, 1500)
            );
        }
    }

    /**
     * Normalize parsed output so your scoring/matching won’t randomly break.
     * Ensures:
     *  - skills => array of strings (normalized)
     *  - years_experience => int
     *  - education => string|null
     *  - entities => included
     */
    protected function normalizeParsed(array $parsed, string $text, array $entities): array
    {
        // 1) Years
        $years =
            (int)($parsed['years_experience'] ?? 0) ? (int)$parsed['years_experience']
            : ((int)($parsed['experience_years'] ?? 0) ? (int)$parsed['experience_years'] : 0);

        if ($years <= 0 && !empty($entities['years_experience'])) {
            $years = (int)$entities['years_experience'];
        }

        // 2) Education
        $education = $parsed['education'] ?? null;
        if (is_array($education)) $education = json_encode($education, JSON_UNESCAPED_UNICODE);

        if ((!is_string($education) || trim($education) === '') && !empty($entities['highest_degree'])) {
            $education = (string)$entities['highest_degree'];
        }

        // 3) Skills (force array)
        $skillsRaw = $parsed['skills'] ?? $parsed['skill'] ?? $parsed['keywords'] ?? [];
        $skills = $this->toSkillArray($skillsRaw);

        // 4) If AI gives no skills, fallback from resume text
        if (empty($skills)) {
            $skills = $this->fallbackSkillsFromText($text);
        }

        // 5) Normalize skills with your SkillNormalizer map
        $skills = app(SkillNormalizer::class)->normalize($skills);

        // 6) Put back normalized values
        $parsed['skills'] = $skills;
        $parsed['years_experience'] = $years;
        $parsed['education'] = $education;

        // store entities too (super useful for debugging)
        $parsed['entities'] = $entities;

        return $parsed;
    }

    /** Convert mixed AI return format into clean string[] */
    protected function toSkillArray($v): array
    {
        if (is_null($v)) return [];

        // If AI gives a string like "a, b, c"
        if (is_string($v)) {
            $arr = preg_split('/[,;\n\r|]+/', $v) ?: [];
            return array_values(array_filter(array_map('trim', $arr)));
        }

        // If AI gives array
        if (is_array($v)) {
            $out = [];
            foreach ($v as $item) {
                if (is_string($item)) {
                    $out[] = trim($item);
                } elseif (is_array($item)) {
                    // common AI formats: {name:"..."}, {skill:"..."}
                    if (!empty($item['name'])) $out[] = trim((string)$item['name']);
                    elseif (!empty($item['skill'])) $out[] = trim((string)$item['skill']);
                    else $out[] = trim(implode(' ', array_map('strval', $item)));
                } else {
                    $out[] = trim((string)$item);
                }
            }
            $out = array_values(array_filter($out, fn($x) => $x !== ''));
            return $out;
        }

        return [];
    }

    /** Small keyword fallback for maintenance/plant resumes */
    protected function fallbackSkillsFromText(string $text): array
    {
        $t = strtolower($text);

        $lex = [
            'preventive maintenance',
            'corrective maintenance',
            'rotating equipment',
            'pump', 'pumps',
            'motor', 'motors',
            'valve', 'valves',
            'pipeline', 'pipelines',
            'piping',
            'gearbox', 'gearboxes',
            'troubleshooting',
            'breakdown',
            'lockout tagout', 'loto',
            'permit to work', 'ptw',
            'p&id', 'pid',
            'cmms',
            'sap pm',
            'condition monitoring',
            'root cause analysis', 'rca',
            'instrument calibration',
            'electrical troubleshooting',
        ];

        $found = [];
        foreach ($lex as $k) {
            if (str_contains($t, $k)) $found[] = $k;
        }

        return array_values(array_unique($found));
    }

    protected function updateStatus(array $cols, ?string $status, ?string $parsed, ?string $error): void
    {
        $payload = [];
        if ($cols['ai_status'])  $payload[$cols['ai_status']]  = $status;
        if ($cols['ai_parsed'])  $payload[$cols['ai_parsed']]  = $parsed;
        if ($cols['ai_error'])   $payload[$cols['ai_error']]   = $error;
        if ($cols['updated_at']) $payload[$cols['updated_at']] = now();

        if ($payload) {
            DB::table('resumes')->where('id', $this->resumeId)->update($payload);
        }
    }

    public function failed(\Throwable $e): void
    {
        // schema-safe update (don’t hardcode column names)
        $cols = [
            'ai_status'  => $this->col('ai_status'),
            'ai_error'   => $this->col('ai_error'),
            'updated_at' => $this->col('updated_at'),
        ];

        $payload = [];
        if ($cols['ai_status'])  $payload[$cols['ai_status']]  = 'error';
        if ($cols['ai_error'])   $payload[$cols['ai_error']]   = mb_substr($e->getMessage(), 0, 1500);
        if ($cols['updated_at']) $payload[$cols['updated_at']] = now();

        if ($payload) {
            DB::table('resumes')->where('id', $this->resumeId)->update($payload);
        }
    }
}
