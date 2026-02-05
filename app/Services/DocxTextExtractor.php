<?php

namespace App\Services;

use ZipArchive;

class DocxTextExtractor
{
    public static function extract(string $absPath): string
    {
        $zip = new ZipArchive();
        $ok = $zip->open($absPath);

        if ($ok !== true) {
            throw new \RuntimeException("Cannot open DOCX file.");
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!is_string($xml) || $xml === '') {
            throw new \RuntimeException("DOCX document.xml missing.");
        }

        // Remove XML tags -> keep text
        $text = strip_tags($xml);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }
}
