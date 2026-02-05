<?php

namespace App\Services;

final class ResumeHeuristicsParser
{
    /**
     * Enrich the AI JSON with normalized fields:
     * - skills[] (lowercased)
     * - years_experience (float)
     * - experiences[]: [{title,company,start,end,years,bullets[]}]
     * - projects[] (FYP-aware)
     * - education (string|array)
     * - certifications[], keywords[]
     */
    public static function enrich(array $ai, ?string $plain = null): array
    {
        // ---- skills
        $skills = $ai['skills'] ?? $ai['skills_list'] ?? [];
        if (is_string($skills)) {
            $skills = preg_split('/[,;|]+/', $skills) ?: [];
        }
        $skills = array_values(array_unique(array_filter(array_map(
            fn($s) => strtolower(trim((string)$s)),
            (array) $skills
        ))));

        // ---- education / certifications / keywords passthrough
        $education      = $ai['education'] ?? ($ai['education_level'] ?? null);
        $certifications = self::toArray($ai['certifications'] ?? []);
        $keywords       = self::toArray($ai['keywords'] ?? []);

        // ---- experiences
        $experiences = self::extractExperiences(
            $ai['experiences'] ?? $ai['work_experience'] ?? [],
            $plain
        );

        // years_experience: prefer structured years, else sum from experiences
        $years = 0.0;
        if (isset($ai['years_experience'])) {
            $years = (float) $ai['years_experience'];
        } elseif (isset($ai['experience_years'])) {
            $years = (float) $ai['experience_years'];
        } else {
            foreach ($experiences as $e) { $years += (float)($e['years'] ?? 0); }
        }

        // ---- FYP / projects
        $projects = self::extractProjects(
            $ai['projects'] ?? [],
            $plain
        );

        $ai['skills']            = $skills;
        $ai['education']         = $education;
        $ai['certifications']    = $certifications;
        $ai['keywords']          = $keywords;
        $ai['experiences']       = $experiences;
        $ai['years_experience']  = round($years, 2);
        $ai['projects']          = $projects;

        return $ai;
    }

    private static function toArray($v): array
    {
        if (is_string($v)) return array_values(array_filter(array_map('trim', preg_split('/[,;|]+/', $v) ?: [])));
        return array_values(array_filter((array)$v));
    }

    private static function extractExperiences($raw, ?string $plain): array
    {
        $out = [];

        // 1) Structured array from AI
        foreach ((array)$raw as $it) {
            $title   = trim((string)($it['title'] ?? $it['role'] ?? ''));
            $company = trim((string)($it['company'] ?? ''));
            $start   = trim((string)($it['start'] ?? $it['from'] ?? ''));
            $end     = trim((string)($it['end'] ?? $it['to'] ?? 'present'));
            $bullets = (array)($it['bullets'] ?? $it['responsibilities'] ?? $it['achievements'] ?? []);
            $years   = isset($it['years']) ? (float)$it['years'] : self::estimateYears($start, $end);

            if ($title || $company) {
                $out[] = [
                    'title' => $title, 'company' => $company,
                    'start' => $start, 'end' => $end,
                    'years' => round($years, 2),
                    'bullets' => array_values(array_filter(array_map('trim', $bullets))),
                ];
            }
        }

        // 2) Heuristic from plain text (if nothing structured came through)
        if (!$out && $plain) {
            // e.g., "Software Engineer — BizOps Sdn Bhd (2022–Present)"
            if (preg_match_all('/(?P<title>[A-Za-z .\/&+-]{2,})\s+[—-]\s+(?P<company>[A-Za-z0-9 .,&+-]{2,})\s*\((?P<start>\d{4})(?:[\/\-–]\d{1,2})?\s*[–-]\s*(?P<end>Present|\d{4}(?:[\/\-–]\d{1,2})?)\)/u', $plain, $m, PREG_SET_ORDER)) {
                foreach ($m as $mm) {
                    $out[] = [
                        'title' => trim($mm['title']),
                        'company' => trim($mm['company']),
                        'start' => $mm['start'],
                        'end'   => $mm['end'],
                        'years' => round(self::estimateYears($mm['start'], $mm['end']), 2),
                        'bullets' => [],
                    ];
                }
            }
        }

        return $out;
    }

    private static function estimateYears(string $start, string $end): float
    {
        $toTs = function(string $s): ?int {
            $s = trim(strtolower($s));
            if ($s === 'present' || $s === 'current') return time();
            // support YYYY or YYYY-MM formats
            if (preg_match('/^(?<y>\d{4})(?:[\/\-–](?<m>\d{1,2}))?$/', $s, $m)) {
                $y = (int)$m['y']; $mm = isset($m['m']) ? (int)$m['m'] : 6;
                return strtotime(sprintf('%04d-%02d-01', $y, max(1, min(12, $mm))));
            }
            return null;
        };
        $a = $toTs($start); $b = $toTs($end);
        if (!$a || !$b || $b <= $a) return 0.0;
        $years = ($b - $a) / (365.25*24*3600);
        return max(0.0, min(50.0, $years));
    }

    private static function extractProjects($raw, ?string $plain): array
    {
        $projects = [];
        foreach ((array)$raw as $p) {
            $title = trim((string)($p['title'] ?? $p['name'] ?? ''));
            $desc  = trim((string)($p['description'] ?? $p['desc'] ?? ''));
            if ($title || $desc) $projects[] = ['title'=>$title,'description'=>$desc];
        }

        // If fresh grad with FYP mentioned in plain text
        if ($plain && stripos($plain, 'final year project') !== false || stripos($plain, 'fyp') !== false) {
            $projects[] = [
                'title' => 'Final Year Project',
                'description' => 'Detected from résumé text (FYP).',
            ];
        }
        return $projects;
    }

    /** Very small relevance helper for controller usage */
    public static function relevantRoles(array $experiences, string $vacancyText, int $limit = 3): array
    {
        $v = strtolower($vacancyText);
        $score = function(array $e) use ($v){
$txt = strtolower(trim(
    ($e['title'] ?? '') . ' ' .
    (string)($e['company'] ?? '') . ' ' .
    implode(' ', $e['bullets'] ?? [])
));

            $overlap = 0;
            foreach (['frontend','ui','react','vue','javascript','typescript','css','tailwind','mobile','flutter'] as $kw) {
                if (str_contains($v, $kw) && str_contains($txt, $kw)) $overlap++;
            }
            return $overlap*100 + (int)round(10*($e['years'] ?? 0));
        };
        usort($experiences, fn($a,$b) => $score($b) <=> $score($a));
        return array_slice($experiences, 0, $limit);
    }
}
