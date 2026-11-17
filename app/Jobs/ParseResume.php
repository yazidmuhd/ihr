<?php

namespace App\Jobs;

use App\Services\AiClient;
use App\Services\DemoResumeParser;
use App\Services\DocumentText;
use App\Services\ResumeEntityExtractor;
use App\Services\SkillNormalizer;
use App\Services\Uploads;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Extracts text from an uploaded résumé and stores the AI-parsed profile in
 * resumes.ai_parsed. Runs on the "high" queue (the worker listens to high,default).
 */
class ParseResume implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(public int $resumeId)
    {
        $this->onQueue('high');
    }

    public function handle(AiClient $ai): void
    {
        $row = DB::table('resumes')->where('id', $this->resumeId)->first();
        if (!$row) return;

        $this->updateStatus('processing');

        try {
            // Works for local and S3 uploads: remote files are copied to a temp file first
            $text = Uploads::withLocalCopy($row->path, fn (string $abs) => DocumentText::extract($abs, $row->mime, $row->filename));

            if (!is_string($text) || mb_strlen(trim($text)) < 50) {
                throw new \RuntimeException('Could not extract enough readable text from the file.');
            }

            // light entities (rule-based, no AI)
            $entities = ResumeEntityExtractor::lightEntities($text);

            // AI parse; the public demo without an AI key uses the rule-based demo reader
            $parsed = config('demo.enabled') && ! $ai->hasKey()
                ? DemoResumeParser::parse($text)
                : $ai->parseResumeText($text);
            if (!is_array($parsed)) {
                throw new \RuntimeException('AI returned an invalid structure.');
            }

            // Normalize AI output into consistent keys/arrays
            $normalized = $this->normalizeParsed($parsed, $text, $entities);

            $this->updateStatus('done', json_encode($normalized, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            $this->updateStatus('error', null, mb_substr($e->getMessage(), 0, 1500));
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

    protected function updateStatus(string $status, ?string $parsed = null, ?string $error = null): void
    {
        DB::table('resumes')->where('id', $this->resumeId)->update([
            'ai_status'  => $status,
            'ai_parsed'  => $parsed,
            'ai_error'   => $error,
            'updated_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $this->updateStatus('error', null, mb_substr($e->getMessage(), 0, 1500));
    }
}
