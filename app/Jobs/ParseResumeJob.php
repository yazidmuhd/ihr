<?php

namespace App\Jobs;

use App\Services\AiResumeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ParseResume implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120; // seconds
    public function __construct(public int $resumeId) {}

    /** Return first existing column on 'resumes' table. */
    protected function col(string ...$names): ?string
    {
        foreach ($names as $n) {
            if (Schema::hasColumn('resumes', $n)) return $n;
        }
        return null;
    }

    public function handle(AiResumeService $ai): void
    {
        $row = DB::table('resumes')->where('id', $this->resumeId)->first();
        if (!$row) return;

        $storageCol = $this->col('storage_path', 'file_path', 'path', 'stored_path');
        $mimeCol    = $this->col('mime_type', 'mime', 'content_type', 'mimetype');
        $statusCol  = $this->col('ai_status');
        $parsedCol  = $this->col('ai_parsed');

        // mark as processing
        if ($statusCol) {
            DB::table('resumes')->where('id', $this->resumeId)->update([$statusCol => 'processing']);
        }

        try {
            $relPath = $storageCol ? ($row->{$storageCol} ?? null) : null;
            if (!$relPath) throw new \RuntimeException('No storage path on resume.');

            $absPath = Storage::disk('public')->path($relPath);
            if (!is_file($absPath)) throw new \RuntimeException('Resume file not found on disk.');

            $mime = $mimeCol ? ($row->{$mimeCol} ?? '') : '';
            $text = $this->extractText($absPath, $mime);

            if (!trim($text)) {
                throw new \RuntimeException('Could not extract text from resume.');
            }

            // Ask the model to extract structured info
            $result = $ai->extractFromText($text);

            DB::table('resumes')->where('id', $this->resumeId)->update(array_filter([
                $parsedCol => $parsedCol ? json_encode($result, JSON_UNESCAPED_UNICODE) : null,
                $statusCol => $statusCol ? 'done' : null,
                'updated_at' => now(),
            ], fn($v) => $v !== null));
        } catch (\Throwable $e) {
            Log::warning('ParseResume failed: '.$e->getMessage(), ['resume_id' => $this->resumeId]);

            DB::table('resumes')->where('id', $this->resumeId)->update(array_filter([
                $statusCol => $statusCol ? 'error' : null,
                'updated_at' => now(),
            ], fn($v) => $v !== null));
        }
    }

    /** Very light-weight text extraction for PDF/DOCX. */
    protected function extractText(string $path, string $mime = ''): string
    {
        $lower = strtolower($path.' '.$mime);

        // PDF: try smalot/pdfparser if available; fallback to shell pdftotext if installed.
        if (str_contains($lower, 'pdf')) {
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($path);
                return $pdf->getText();
            }
            // Fallback to `pdftotext` if present (optional)
            if (trim(shell_exec('which pdftotext'))) {
                $tmp = $path.'.txt';
                @shell_exec('pdftotext '.escapeshellarg($path).' '.escapeshellarg($tmp));
                return is_file($tmp) ? file_get_contents($tmp) : '';
            }
        }

        // DOCX (WordprocessingML): unzip and strip XML
        if (str_contains($lower, 'docx') || str_contains($lower, 'wordprocessingml')) {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                $xml = $zip->getFromName('word/document.xml') ?: '';
                $zip->close();
                if ($xml) {
                    $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml);
                    $xml = preg_replace('/<\/w:p>/', "\n", $xml);
                    return trim(strip_tags($xml));
                }
            }
        }

        // Plain text fallback
        if (str_starts_with($mime, 'text/')) {
            return file_get_contents($path);
        }

        return '';
    }
}
