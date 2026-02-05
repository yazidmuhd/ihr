<?php

namespace App\Services;

class ResumeParser
{
    public static function parse(string $absPath, ?string $mime = null, ?string $name = null): array
    {
        $text = DocumentText::extract($absPath, $mime, $name);
        return ['text' => $text];
    }
}
