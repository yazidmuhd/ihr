<?php

namespace App\Services;

class PdfTextExtractor
{
    public static function extract(string $absPath): string
    {
        // Best option: pdftotext (Poppler)
        $cmd = 'pdftotext ' . escapeshellarg($absPath) . ' - 2>/dev/null';
        $out = @shell_exec($cmd);

        if (is_string($out) && mb_strlen(trim($out)) > 30) {
            return $out;
        }

        // If pdftotext not available, throw with clear instruction
        throw new \RuntimeException(
            "PDF text extraction failed. Install Poppler pdftotext and retry. (macOS: brew install poppler)"
        );
    }
}
