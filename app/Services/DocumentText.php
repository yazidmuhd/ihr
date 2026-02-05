<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;
use Illuminate\Support\Str;
use ZipArchive;

class DocumentText
{
    /**
     * Extract UTF-8 text from a résumé file (PDF or DOCX).
     * Falls back to raw file contents if unknown type.
     */
    public static function extract(string $absolutePath, ?string $mime, ?string $originalName = null): string
    {
        $mime = strtolower((string)$mime);
        $name = strtolower((string)$originalName);

        // Prefer exact mime, then extension hint
        if (str_contains($mime, 'pdf') || Str::endsWith($name, '.pdf')) {
            return self::fromPdf($absolutePath);
        }

        if (str_contains($mime, 'wordprocessingml') || Str::endsWith($name, '.docx')) {
            return self::fromDocx($absolutePath);
        }

        // Legacy .doc not supported well; treat as binary
        if (Str::endsWith($name, '.doc')) {
            throw new \RuntimeException('Legacy .doc is not supported. Please upload PDF or DOCX.');
        }

        // Fallback: try to read as text
        $raw = @file_get_contents($absolutePath);
        if ($raw === false) {
            throw new \RuntimeException('Unable to read file from storage.');
        }
        return mb_convert_encoding($raw, 'UTF-8', 'UTF-8');
    }

    protected static function fromPdf(string $path): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);
        $text = $pdf->getText() ?? '';
        $text = preg_replace("/\s+/", " ", $text);
        return trim((string)$text);
    }

    protected static function fromDocx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to open DOCX container.');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            throw new \RuntimeException('DOCX content not found.');
        }
        // Strip XML tags, keep simple spacing
        $text = preg_replace('/<w:p[^>]*>/i', "\n", $xml);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace("/[ \t]+/", " ", $text);
        $text = preg_replace("/\n{2,}/", "\n", $text);
        return trim($text);
    }
}
