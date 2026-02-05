<?php

// app/Jobs/ProcessResumeAi.php
namespace App\Jobs;

use App\Services\ResumeNLPService;
use App\Services\MatchScorer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessResumeAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $resumeId, public int $applicantId) {}

    public function handle(ResumeNLPService $nlp, MatchScorer $scorer): void
    {
        $resume = DB::table('resumes')->where('id', $this->resumeId)->first();
        if (!$resume || !$resume->file_path) return;

        // 1) Extract text (very light-weight helpers)
        $text = $this->extractText($resume->file_path);

        // 2) AI → structured profile
        $profile = $nlp->extractProfile($text);

        // 3) Save profile
        DB::table('resumes')->where('id', $this->resumeId)->update([
            'ai_profile'    => json_encode($profile),
            'ai_status'     => 'ready',
            'ai_error'      => null,
            'ai_updated_at' => now(),
            'updated_at'    => now(),
        ]);

        // 4) Score against all Open vacancies
        $vacancies = DB::table('vacancies')->where('status','Open')->get(['id','requirements','title']);
        foreach ($vacancies as $v) {
            $req = $v->requirements ? json_decode($v->requirements, true) : [];
            $res = $scorer->score($profile, $req);

            DB::table('ai_scores')->upsert([[
                'applicant_id' => $this->applicantId,
                'resume_id'    => $this->resumeId,
                'vacancy_id'   => $v->id,
                'score'        => $res['score'],
                'details'      => json_encode($res['details']),
                'updated_at'   => now(),
                'created_at'   => now(),
            ]], uniqueBy: ['applicant_id','resume_id','vacancy_id'], update: ['score','details','updated_at']);
        }
    }

    private function extractText(string $path): string
    {
        $abs = Storage::disk('public')->path($path);

        // quick PDF text if available (optional: install smalot/pdfparser or spatie/pdf-to-text)
        if (str_ends_with(strtolower($abs), '.pdf') && function_exists('shell_exec')) {
            // Try `pdftotext` if installed (very fast); else fall back to raw
            @exec("pdftotext -layout ".escapeshellarg($abs)." -", $out, $code);
            if (($out ?? null) && $code === 0) return trim(implode("\n", $out));
        }

        // DOCX raw unzip
        if (str_ends_with(strtolower($abs), '.docx') && class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($abs) === true) {
                $xml = $zip->getFromName('word/document.xml') ?: '';
                $zip->close();
                return trim(strip_tags($xml));
            }
        }

        // safest fallback: try read file as text
        return trim(@file_get_contents($abs) ?: '');
    }

    public function failed(\Throwable $e): void
    {
        DB::table('resumes')->where('id',$this->resumeId)->update([
            'ai_status'  => 'error',
            'ai_error'   => $e->getMessage(),
            'updated_at' => now(),
        ]);
    }
}
