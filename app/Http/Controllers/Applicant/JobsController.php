<?php

namespace App\Services;

class MatchScorer
{
    /** Canonical skills dictionary (extend anytime) */
    protected static array $canon = [
        'php'         => ['php'],
        'laravel'     => ['laravel'],
        'vue'         => ['vue', 'vue.js', 'vuejs'],
        'react'       => ['react', 'react.js', 'reactjs'],
        'node'        => ['node', 'node.js', 'nodejs'],
        'typescript'  => ['typescript', 'ts'],
        'javascript'  => ['javascript', 'js'],
        'postgresql'  => ['postgres', 'postgresql', 'psql'],
        'mysql'       => ['mysql'],
        'sql'         => ['sql'],
        'docker'      => ['docker'],
        'aws'         => ['aws', 'amazon web services'],
        'git'         => ['git', 'github', 'gitlab'],
        'tailwind'    => ['tailwind', 'tailwindcss'],
        'html'        => ['html', 'html5'],
        'css'         => ['css', 'css3'],
        'python'      => ['python'],
        'java'        => ['java'],
        'firebase'    => ['firebase'],
        'flutter'     => ['flutter', 'dart'],
    ];

    /** Safely convert anything-ish to a lowercase string */
    protected static function toString(mixed $v): string
    {
        if (is_string($v) || is_numeric($v)) return mb_strtolower((string)$v);
        if (is_object($v)) $v = (array)$v;
        if (is_array($v))  return mb_strtolower(trim(implode(' ', array_map([self::class,'toString'], $v))));
        return '';
    }

    /** Recursively flatten skills array/object to a list of strings */
    protected static function flattenSkills(mixed $skills): array
    {
        $out = [];
        $walk = function ($x) use (&$out, &$walk) {
            if (is_null($x)) return;

            if (is_string($x) || is_numeric($x)) {
                $s = trim((string)$x);
                if ($s !== '') $out[] = $s;
                return;
            }

            if (is_object($x)) $x = (array)$x;
            if (is_array($x)) {
                // Common property names seen in parsers
                $candidates = ['name','skill','title','keyword','technology','tech','value'];
                $picked = null;
                foreach ($candidates as $k) {
                    if (isset($x[$k]) && (is_string($x[$k]) || is_numeric($x[$k]))) {
                        $picked = (string)$x[$k];
                        break;
                    }
                }
                if ($picked !== null) {
                    $picked = trim($picked);
                    if ($picked !== '') $out[] = $picked;
                } else {
                    foreach ($x as $v) $walk($v);
                }
            }
        };
        $walk($skills);

        // unique, keep order
        $seen = [];
        $uniq = [];
        foreach ($out as $s) {
            $k = mb_strtolower($s);
            if (!isset($seen[$k])) { $seen[$k] = true; $uniq[] = $s; }
        }
        return $uniq;
    }

    /** Normalize free text into canonical skills keys */
    protected static function normalizeSkillsFromText(string $text): array
    {
        $t = mb_strtolower($text);
        $found = [];
        foreach (self::$canon as $key => $aliases) {
            foreach ($aliases as $a) {
                if (preg_match('/\b'.preg_quote($a, '/').'\b/u', $t)) {
                    $found[$key] = true; break;
                }
            }
        }
        return array_keys($found);
    }

    /** Normalize an array/object of skills into canonical keys */
    protected static function normalizeSkillsArray(array|object $skills): array
    {
        $flat = self::flattenSkills($skills);
        $joined = ' ' . mb_strtolower(implode(' ', $flat)) . ' ';
        return self::normalizeSkillsFromText($joined);
    }

    /** Extract vacancy signals (min years, education, skills) from text */
    protected static function vacancySignals(array $vacancy): array
    {
        $title = self::toString($vacancy['title'] ?? '');
        $desc  = self::toString($vacancy['description'] ?? '');
        $text  = trim($title . "\n" . $desc);

        $minYears = 0;
        if (preg_match('/(\d+)\s*\+\s*years/i', $text, $m)) {
            $minYears = (int)$m[1];
        } elseif (preg_match('/min(?:imum)?\s+(\d+)\s+years?/i', $text, $m)) {
            $minYears = (int)$m[1];
        }

        $edu = null;
        if (preg_match('/master/i',   $text)) $edu = 'master';
        if (preg_match('/bachelor|degree/i', $text)) $edu = $edu ?: 'bachelor';
        if (preg_match('/diploma/i',  $text)) $edu = $edu ?: 'diploma';

        $skills = self::normalizeSkillsFromText($text);

        return [
            'min_years' => $minYears,
            'education' => $edu,
            'skills'    => $skills,
        ];
    }

    /** Score 0–100 with a simple weighted formula */
    public static function score(array $resumeParsed, array $vacancy): array
    {
        // skills may be strings, arrays, objects → normalize robustly
        $resumeSkills = self::normalizeSkillsArray((array)($resumeParsed['skills'] ?? []));
        $resumeYears  = (int)($resumeParsed['years_experience'] ?? 0);

        // education may be string/array/object
        $resumeEduStr = self::toString($resumeParsed['education'] ?? '');

        $v = self::vacancySignals($vacancy);

        // Skills (70)
        $overlap = array_values(array_intersect($resumeSkills, $v['skills']));
        $denom   = max(count($v['skills']), 1);
        $skillsScore = (int)round((count($overlap) / $denom) * 70);

        // Experience (20)
        $expScore = ($v['min_years'] <= 0)
            ? min($resumeYears, 5) * 4              // cap 5y => 20
            : (int)round(min($resumeYears / max($v['min_years'],1), 1) * 20);

        // Education (10)
        $eduScore = 0;
        if ($v['education']) {
            $map = ['diploma' => 1, 'bachelor' => 2, 'master' => 3];
            $r = 0;
            foreach ($map as $k => $rank) {
                if ($resumeEduStr !== '' && str_contains($resumeEduStr, $k)) { $r = $rank; break; }
            }
            $need = $map[$v['education']] ?? 0;
            $eduScore = $r >= $need ? 10 : 0;
        } else {
            $eduScore = 5;
        }

        $total = max(0, min(100, $skillsScore + $expScore + $eduScore));

        return [
            'score' => $total,
            'breakdown' => [
                'skills'            => $skillsScore,
                'experience'        => $expScore,
                'education'         => $eduScore,
                'overlap_skills'    => $overlap,
                'resume_skills'     => array_values($resumeSkills),
                'vacancy_skills'    => array_values($v['skills']),
                'vacancy_min_years' => $v['min_years'],
                'vacancy_education' => $v['education'],
            ],
        ];
    }
}
