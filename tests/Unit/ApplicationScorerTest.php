<?php

namespace Tests\Unit;

use App\Services\ApplicationScorer;
use Tests\TestCase;

class ApplicationScorerTest extends TestCase
{
    private function vacancyRow(array $overrides = []): object
    {
        return (object) ($overrides + [
            'title'                => 'Data Analyst',
            'description'          => 'SQL and Python analytics',
            'skills_required'      => json_encode(['SQL', 'Python', 'Excel']),
            'experience_min_years' => 0,
            'experience_max_years' => 3,
            'education_required'   => null,
        ]);
    }

    private function resumeRow(array $parsed): object
    {
        return (object) ['ai_parsed' => json_encode($parsed)];
    }

    public function test_a_matching_resume_scores_higher_than_an_unrelated_one(): void
    {
        $match = ApplicationScorer::score($this->resumeRow(['skills' => ['SQL', 'Python', 'Excel'], 'experience_years' => 2]), $this->vacancyRow());
        $other = ApplicationScorer::score($this->resumeRow(['skills' => ['Cooking', 'Driving'], 'experience_years' => 2]), $this->vacancyRow());

        $this->assertGreaterThan($other['score'], $match['score']);
        $this->assertLessThanOrEqual(100, $match['score']);
        $this->assertIsArray($match['breakdown']);
    }

    public function test_an_unparsed_resume_scores_without_errors(): void
    {
        $result = ApplicationScorer::score((object) ['ai_parsed' => null], $this->vacancyRow());

        $this->assertGreaterThanOrEqual(0, $result['score']);
    }

    public function test_experience_is_derived_from_work_history_when_not_given(): void
    {
        $norm = ApplicationScorer::normalizeResume($this->resumeRow([
            'work' => [['start' => '2020-01', 'end' => '2023-01']],
        ]));

        $this->assertSame(3, $norm['experience_years']);
    }
}
