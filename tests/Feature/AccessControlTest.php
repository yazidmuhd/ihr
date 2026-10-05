<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/hr/dashboard')->assertRedirect('/login');
        $this->get('/applicant/dashboard')->assertRedirect('/login');
    }

    public function test_applicants_cannot_open_hr_pages(): void
    {
        [$user] = $this->applicant();

        foreach (['/hr/dashboard', '/hr/vacancies', '/hr/activity', '/hr/employees'] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_hr_cannot_open_applicant_pages(): void
    {
        $this->actingAs($this->hrUser())->get('/applicant/dashboard')->assertForbidden();
    }

    public function test_hr_pages_load(): void
    {
        $hr = $this->hrUser();
        $vacancyId = $this->vacancy();

        foreach (['/hr/dashboard', '/hr/vacancies', '/hr/vacancies/create', "/hr/vacancies/{$vacancyId}/edit",
                  "/hr/vacancies/{$vacancyId}/ai", '/hr/shortlist', '/hr/interviews', '/hr/employees', '/hr/activity'] as $url) {
            $this->actingAs($hr)->get($url)->assertOk();
        }
    }

    public function test_applicant_pages_load(): void
    {
        [$user] = $this->applicant();
        $vacancyId = $this->vacancy();

        foreach (['/applicant/dashboard', '/jobs', "/jobs/{$vacancyId}", '/applications', '/app/resume', '/app/interviews', '/profile'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_debug_routes_do_not_exist_outside_local(): void
    {
        $this->assertFalse(app()->environment('local'));

        $this->get('/debug/db')->assertNotFound();
        $this->actingAs($this->hrUser())->get('/debug/ai')->assertNotFound();
    }
}
