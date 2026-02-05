<?php

namespace App\Services;

use App\Models\VacancyRule;

class ResumeScoringService
{
    public function score(string $rawText, int $vacancyId, array $entities = []): array
    {
        $rules = VacancyRule::where('vacancy_id', $vacancyId)->get();
        $text = ' ' . $rawText . ' ';
        $section = ['skill'=>0,'education'=>0,'experience'=>0,'cert'=>0,'softskill'=>0];
        $spans = [];
        $total = 0;

        foreach ($rules as $r) {
            $pattern = $r->is_regex
                ? $r->pattern
                : '/\b' . preg_quote(mb_strtolower($r->pattern), '/') . '\b/u';

            if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                $count = count($matches[0]);
                $gain  = $r->weight * $count;
                $section[$r->rule_type] += $gain;
                $total += $gain;
                foreach ($matches[0] as [$tok, $pos]) {
                    $spans[] = ['pattern'=>$r->pattern,'start'=>$pos,'end'=>$pos+mb_strlen($tok)];
                }
            }
        }

        if (!empty($entities['highest_degree'])) {
            $boost = match ($entities['highest_degree']) {
                'phd','doctorate' => 10,
                'master','msc'    => 7,
                'bachelor','bsc'  => 5,
                'diploma'         => 3,
                default           => 0
            };
            $section['education'] += $boost; $total += $boost;
        }
        if (!empty($entities['years_experience'])) {
            $yr = (int)$entities['years_experience'];
            $expBoost = min(10, intdiv($yr,2));
            $section['experience'] += $expBoost; $total += $expBoost;
        }

        return ['total'=>$total,'sections'=>$section,'spans'=>$spans];
    }
}
