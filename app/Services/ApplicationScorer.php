<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Scores a résumé (resumes.ai_parsed) against a vacancy.
 * Shared by the applicant "Apply" flow and the HR "Rescore" action so both
 * produce the same match_score / match_breakdown.
 */
class ApplicationScorer
{
    /** @return array{score:int|float, breakdown:array} */
    public static function score(object $resume, object $vacancy): array
    {
        $norm = self::normalizeResume($resume);

        $payload = array_merge($norm['raw'], [
            'skills'           => $norm['skills'],
            'experience_years' => $norm['experience_years'],
            'education'        => $norm['education'],
            'certifications'   => $norm['certifications'],
            'keywords'         => $norm['keywords'],
        ]);

        return MatchScorer::score($payload, self::vacancyPayload($vacancy));
    }

    public static function vacancyPayload(object $vacancy): array
    {
        $skillsRequired = $vacancy->skills_required ?? null;
        if (is_string($skillsRequired)) {
            $decoded = json_decode($skillsRequired, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $skillsRequired = $decoded;
            }
        }

        return [
            'title'                => $vacancy->title ?? '',
            'description'          => $vacancy->description ?? '',
            'requirements'         => $vacancy->description ?? '',
            'skills_required'      => $skillsRequired,
            'experience_min_years' => $vacancy->experience_min_years ?? null,
            'experience_max_years' => $vacancy->experience_max_years ?? null,
            'education_required'   => $vacancy->education_required ?? null,
        ];
    }

    /**
     * Normalize AI-parsed résumé JSON to stable keys for scoring.
     */
    public static function normalizeResume(object $resume): array
    {
        if (empty($resume->ai_parsed)) {
            return [
                'raw' => [], 'skills' => [], 'experience_years' => 0, 'education' => null,
                'certifications' => [], 'keywords' => [], 'experiences' => [], 'projects' => [],
            ];
        }

        $raw  = $resume->ai_parsed;
        $json = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);

        try {
            $json = ResumeHeuristicsParser::enrich($json, null);
        } catch (\Throwable $e) {}

        $skills = $json['skills'] ?? $json['skills_list'] ?? [];
        if (is_string($skills)) $skills = preg_split('/[,;|]+/', $skills) ?: [];
        $skills = array_values(array_unique(array_filter(array_map(
            fn ($s) => strtolower(trim((string) $s)),
            (array) $skills
        ))));

        $expValue = null;
        foreach ([
            'experience_years', 'years_experience', 'total_experience_years',
            'years_of_experience', 'exp_years', 'experience'
        ] as $k) {
            if (array_key_exists($k, $json)) { $expValue = $json[$k]; break; }
        }
        if (is_array($expValue)) $expValue = $expValue['years'] ?? $expValue['total'] ?? null;

        $expYears = (float) ($expValue ?? 0);

        if ($expYears <= 0 && !empty($json['work']) && is_array($json['work'])) {
            $expYears = (float) self::deriveYearsFromWork($json['work']);
        }

        if ($expYears <= 0 && !empty($json['projects'])) {
            foreach ((array) $json['projects'] as $p) {
                $name = strtolower(trim((string) ($p['name'] ?? $p['title'] ?? '')));
                if (str_contains($name, 'final year project') || str_contains($name, 'capstone')) {
                    $expYears = max($expYears, 1.0);
                    break;
                }
            }
        }

        $education = $json['education'] ?? ($json['education_level'] ?? null);

        $certs = $json['certifications'] ?? [];
        if (is_string($certs)) $certs = preg_split('/[,;|]+/', $certs) ?: [];
        $certs = array_values(array_filter(array_map('trim', (array) $certs)));

        $keywords = $json['keywords'] ?? [];
        if (is_string($keywords)) $keywords = preg_split('/[,;|]+/', $keywords) ?: [];
        $keywords = array_values(array_filter(array_map('trim', (array) $keywords)));

        return [
            'raw'               => $json,
            'skills'            => $skills,
            'experience_years'  => max(0, (int) round($expYears)),
            'education'         => $education,
            'certifications'    => $certs,
            'keywords'          => $keywords,
            'experiences'       => $json['work'] ?? $json['experiences'] ?? [],
            'projects'          => $json['projects'] ?? [],
        ];
    }

    private static function deriveYearsFromWork(array $work): int
    {
        $totalMonths = 0;

        foreach ($work as $w) {
            $start = $w['start'] ?? $w['from'] ?? $w['start_date'] ?? null;
            $end   = $w['end']   ?? $w['to']   ?? $w['end_date']   ?? null;
            if (!$start) continue;

            $s = self::parseAnyDate($start);
            $e = $end ? self::parseAnyDate($end) : Carbon::now();
            if (!$s || !$e) continue;

            $months = max(0, ($e->year - $s->year) * 12 + ($e->month - $s->month));

            if ($months === 0 && !empty($w['duration'])) {
                if (preg_match('/(\d+(?:\.\d+)?)\s*years?/i', $w['duration'], $m)) {
                    $months = (int) round((float) $m[1] * 12);
                } elseif (preg_match('/(\d+)\s*months?/i', $w['duration'], $m)) {
                    $months = (int) $m[1];
                }
            }

            $totalMonths += $months;
        }

        return (int) floor($totalMonths / 12);
    }

    private static function parseAnyDate($value): ?Carbon
    {
        $txt = trim((string) $value);
        if ($txt === '') return null;

        $lower = strtolower($txt);
        if (in_array($lower, ['present', 'current', 'now'], true)) {
            return Carbon::now()->startOfMonth();
        }

        try {
            $txt = preg_replace('/[–—]/', '-', $txt);
            if (str_contains($txt, ' - ')) $txt = trim(explode(' - ', $txt)[0]);
            $c = Carbon::parse($txt);
            return $c ? $c->startOfMonth() : null;
        } catch (\Throwable $e) {}

        foreach (['Y-m-d', 'Y-m', 'Y/m/d', 'Y/m', 'M Y', 'F Y', 'Y'] as $fmt) {
            try {
                $c = Carbon::createFromFormat($fmt, (string) $value);
                if ($c) return $c->startOfMonth();
            } catch (\Throwable $e) {}
        }
        return null;
    }
}
