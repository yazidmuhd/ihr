<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Reads a résumé without an AI provider, for the public demo (DEMO_MODE with no AI_API_KEY).
 *
 * Skills are matched against the skills the open vacancies ask for, plus a short common list;
 * years and education come from the same rule-based extractor the AI path already uses.
 * The result is marked 'demo' => true so the app can label it as a demo analysis.
 */
class DemoResumeParser
{
    private const COMMON_SKILLS = [
        'Laravel', 'PHP', 'PostgreSQL', 'MySQL', 'SQL', 'Vue', 'JavaScript', 'TypeScript', 'React', 'Python',
        'Java', 'REST API', 'Git', 'Docker', 'Linux', 'Microsoft Excel', 'Power BI', 'Recruitment',
        'Onboarding', 'Payroll', 'Communication', 'Project Management',
    ];

    private const DEGREES = [
        'phd' => 'PhD', 'doctorate' => 'PhD', 'master' => "Master's Degree", 'msc' => "Master's Degree",
        'bachelor' => "Bachelor's Degree", 'degree' => "Bachelor's Degree", 'bsc' => "Bachelor's Degree",
        'beng' => "Bachelor's Degree", 'diploma' => 'Diploma', 'spm' => 'SPM',
    ];

    public static function parse(string $text): array
    {
        $entities = ResumeEntityExtractor::lightEntities($text);

        $skills = [];
        foreach (self::vocabulary() as $skill) {
            if (preg_match('/(?<![\w])' . preg_quote($skill, '/') . '(?![\w])/i', $text)) {
                $skills[] = $skill;
            }
        }

        return [
            'full_name'        => null,
            'email'            => $entities['extracted_email'] ?? null,
            'phone'            => null,
            'skills'           => array_values(array_unique($skills)),
            'education'        => self::DEGREES[$entities['highest_degree'] ?? ''] ?? null,
            'years_experience' => (int) ($entities['years_experience'] ?? 0),
            'demo'             => true,
        ];
    }

    /** @return string[] */
    private static function vocabulary(): array
    {
        $fromVacancies = DB::table('vacancies')->whereNotNull('skills_required')->pluck('skills_required')
            ->flatMap(fn ($json) => json_decode($json, true) ?: [])
            ->filter(fn ($s) => is_string($s) && trim($s) !== '')
            ->all();

        return array_values(array_unique(array_merge($fromVacancies, self::COMMON_SKILLS)));
    }
}
