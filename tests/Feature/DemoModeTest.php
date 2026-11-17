<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The public live demo (docs/LIVE_DEMO.md): off unless DEMO_MODE=true.
 */
class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    private function enableDemo(): void
    {
        config(['demo.enabled' => true]);
        Storage::fake('public');
    }

    // ── Off by default ──────────────────────────────────────────────

    public function test_demo_mode_is_off_by_default(): void
    {
        $this->assertFalse(config('demo.enabled'));

        $this->get('/login')->assertOk()
            ->assertDontSee('Demo version of i-HR')
            ->assertInertia(fn ($page) => $page->where('demo', null));
    }

    public function test_demo_reset_refuses_to_run_outside_demo_mode(): void
    {
        $hr = $this->hrUser();

        $this->artisan('demo:reset')->assertFailed();

        $this->assertDatabaseHas('users', ['id' => $hr->id]);   // nothing was wiped
    }

    // ── Demo data and accounts ──────────────────────────────────────

    public function test_demo_seeder_creates_the_accounts_and_fictional_data(): void
    {
        $this->enableDemo();
        $this->seed(DemoSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'hr@demo.ihr', 'role' => 'hr', 'is_hr' => true]);
        $this->assertDatabaseHas('users', ['email' => 'applicant@demo.ihr', 'role' => 'applicant']);
        $this->assertGreaterThanOrEqual(5, DB::table('vacancies')->count());
        $this->assertGreaterThanOrEqual(15, DB::table('applicants')->count());
        $this->assertGreaterThan(0, DB::table('employees')->count());
        $this->assertSame(0, DB::table('applications')->whereNull('match_score')->count());

        // Fictional only: every seeded applicant uses a reserved example address or the demo domain
        $this->assertSame(0, DB::table('users')
            ->where('email', 'not like', '%@example.com')->where('email', 'not like', '%@demo.ihr')->count());
    }

    public function test_both_demo_accounts_can_sign_in_with_the_shown_password(): void
    {
        $this->enableDemo();
        $this->seed(DemoSeeder::class);

        $this->post('/login', ['login' => 'hr@demo.ihr', 'password' => config('demo.password')])
            ->assertRedirect();
        $this->assertAuthenticatedAs(User::where('email', 'hr@demo.ihr')->first());
        $this->post('/logout');

        $this->post('/login', ['login' => 'applicant@demo.ihr', 'password' => config('demo.password')])
            ->assertRedirect();
        $this->assertAuthenticatedAs(User::where('email', 'applicant@demo.ihr')->first());
    }

    public function test_login_page_shows_the_demo_accounts_and_banner(): void
    {
        $this->enableDemo();

        $this->get('/login')->assertOk()
            ->assertSee('Demo version of i-HR')
            ->assertInertia(fn ($page) => $page
                ->where('demo.password', config('demo.password'))
                ->has('demo.accounts', 2));
    }

    // ── Protections ─────────────────────────────────────────────────

    public function test_vacancies_cannot_be_deleted_in_the_demo(): void
    {
        $this->enableDemo();
        $vacancyId = $this->vacancy();

        $this->actingAs($this->hrUser())->delete("/hr/vacancies/{$vacancyId}")
            ->assertStatus(303)   // a 302 would make the browser repeat the DELETE
            ->assertSessionHasErrors('demo');

        $this->assertDatabaseHas('vacancies', ['id' => $vacancyId]);
    }

    public function test_demo_account_passwords_cannot_be_reset(): void
    {
        $this->enableDemo();

        $this->post('/password/email', ['email' => 'hr@demo.ihr'])
            ->assertSessionHas('status', 'Demo account passwords cannot be changed.');
    }

    public function test_the_demo_accepts_pdf_resumes_only(): void
    {
        $this->enableDemo();
        [$user] = $this->applicant();

        $this->actingAs($user)->post('/app/resume', [
            'resume' => UploadedFile::fake()->create('cv.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ])->assertSessionHasErrors('resume');
    }

    // ── Option B: résumé reading without an AI key ──────────────────

    public function test_resumes_are_read_without_an_ai_key_and_labelled_as_demo(): void
    {
        $this->enableDemo();
        $this->vacancy();   // its required skills form part of what the demo reader looks for
        [$user, $applicantId] = $this->applicant();

        $pdf = new UploadedFile(database_path('demo/resumes/software-engineer.pdf'), 'software-engineer.pdf', 'application/pdf', null, true);
        $this->actingAs($user)->post('/app/resume', ['resume' => $pdf])->assertRedirect();

        $resume = DB::table('resumes')->where('applicant_id', $applicantId)->first();
        $parsed = json_decode($resume->ai_parsed, true);

        $this->assertSame('done', $resume->ai_status);
        $this->assertTrue($parsed['demo']);
        $this->assertContains('Laravel', $parsed['skills']);
        $this->assertSame(4, $parsed['years_experience']);
    }

    public function test_without_demo_mode_a_missing_ai_key_still_fails_as_before(): void
    {
        Storage::fake('public');
        [$user, $applicantId] = $this->applicant();

        $pdf = new UploadedFile(database_path('demo/resumes/software-engineer.pdf'), 'software-engineer.pdf', 'application/pdf', null, true);
        $this->actingAs($user)->post('/app/resume', ['resume' => $pdf]);

        $this->assertSame('error', DB::table('resumes')->where('applicant_id', $applicantId)->value('ai_status'));
    }
}
