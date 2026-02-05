<?php

namespace App\Services;

class ResumeEntityExtractor
{
    /**
     * Light entity extraction from raw resume text (no AI).
     * Returns:
     *  - years_experience (int)
     *  - highest_degree (string|null)  // e.g. diploma, bachelor, master, phd
     *  - extracted_email (string|null)
     */
    public static function lightEntities(string $text): array
    {
        $t = strtolower($text);

        $entities = [
            'years_experience' => 0,
            'highest_degree'   => null,
            'extracted_email'  => null,
        ];

        // Years of experience: take MAX found to avoid "1 year internship" overriding "5 years"
        if (preg_match_all('/(\d+)\s*\+?\s*(?:years?|yrs?)/i', $t, $m)) {
            $vals = array_map('intval', $m[1] ?? []);
            $entities['years_experience'] = $vals ? max($vals) : 0;
        }

        // Highest degree: detect all, pick the highest rank
        $degreeRank = [
            'phd'       => 5,
            'doctorate' => 5,
            'master'    => 4,
            'msc'       => 4,
            'bachelor'  => 3,
            'degree'    => 3,
            'bsc'       => 3,
            'beng'      => 3,
            'diploma'   => 2,
            'spm'       => 1,
        ];

        $best = null;
        $bestRank = 0;

        foreach ($degreeRank as $kw => $rank) {
            if (str_contains($t, $kw) && $rank > $bestRank) {
                $best = $kw;
                $bestRank = $rank;
            }
        }

        $entities['highest_degree'] = $best;

        // Email
        if (preg_match('/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.[a-z]{2,}(?:\.[a-z]{2})?/i', $t, $m)) {
            $entities['extracted_email'] = $m[0] ?? null;
        }

        return $entities;
    }
}
