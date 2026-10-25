<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Tests\TestCase;

/**
 * The enhancements described in docs/SECURITY.md.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    // ── Password policy ─────────────────────────────────────────────

    public function test_weak_passwords_are_rejected_at_registration(): void
    {
        foreach (['short1A', 'alllowercase123', 'ALLUPPERCASE123', 'NoNumbersHereAtAll'] as $weak) {
            $this->post('/register', [
                'name' => 'Weak', 'email' => "weak{$weak}@example.com",
                'password' => $weak, 'password_confirmation' => $weak,
            ])->assertSessionHasErrors('password');
        }

        $this->assertSame(0, User::where('email', 'like', 'weak%')->count());
    }

    public function test_a_strong_password_is_accepted(): void
    {
        $this->post('/register', [
            'name' => 'Strong', 'email' => 'strong@example.com',
            'password' => 'Kuala-Lumpur2026', 'password_confirmation' => 'Kuala-Lumpur2026',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'strong@example.com']);
    }

    // ── Security headers ────────────────────────────────────────────

    public function test_pages_send_security_headers(): void
    {
        $response = $this->get('/login')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');   // plain HTTP

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }

    public function test_https_responses_add_hsts(): void
    {
        $this->get('https://localhost/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_the_storage_bucket_may_be_framed_for_resume_previews(): void
    {
        config(['filesystems.disks.s3.endpoint' => 'https://storage.railway.app']);

        $csp = $this->get('/login')->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-src 'self' https://storage.railway.app https://*.storage.railway.app", $csp);
    }

    public function test_csp_can_be_switched_to_report_only_or_off(): void
    {
        config(['security.csp' => 'report']);
        $this->get('/login')->assertHeaderMissing('Content-Security-Policy')
            ->assertHeader('Content-Security-Policy-Report-Only');

        config(['security.csp' => 'off']);
        $this->get('/login')->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    // ── HR idle timeout ─────────────────────────────────────────────

    public function test_idle_hr_session_is_signed_out(): void
    {
        $hr = $this->hrUser();

        $this->actingAs($hr)->get('/hr/dashboard')->assertOk();

        $this->travel(29)->minutes();
        $this->get('/hr/dashboard')->assertOk();            // activity resets the timer

        $this->travel(31)->minutes();
        $this->get('/hr/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_applicants_are_not_affected_by_the_hr_idle_timeout(): void
    {
        [$user] = $this->applicant();

        $this->actingAs($user)->get('/applicant/dashboard')->assertOk();
        $this->travel(60)->minutes();
        $this->get('/applicant/dashboard')->assertOk();
    }

    // ── Disabling accounts ──────────────────────────────────────────

    public function test_disabled_account_cannot_sign_in(): void
    {
        $this->hrUser();                                     // another active HR account exists
        $hr = User::factory()->hr()->create(['username' => 'leaver']);

        $this->artisan('ihr:account', ['action' => 'disable', 'login' => 'leaver'])->assertSuccessful();

        $this->post('/login', ['login' => 'leaver', 'password' => 'password'])
            ->assertSessionHasErrors(['login' => 'This account has been disabled. Please contact HR.']);
        $this->assertGuest();

        $this->artisan('ihr:account', ['action' => 'enable', 'login' => $hr->email])->assertSuccessful();
        $this->post('/login', ['login' => 'leaver', 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($hr->fresh());
    }

    public function test_an_open_session_is_ended_when_the_account_is_disabled(): void
    {
        $this->hrUser();
        $hr = User::factory()->hr()->create();

        $this->actingAs($hr)->get('/hr/dashboard')->assertOk();

        DB::table('users')->where('id', $hr->id)->update(['is_active' => false]);
        $this->actingAs($hr->fresh())->get('/hr/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_the_last_active_hr_account_cannot_be_disabled_by_accident(): void
    {
        $hr = $this->hrUser();

        $this->artisan('ihr:account', ['action' => 'disable', 'login' => $hr->email])->assertFailed();
        $this->assertTrue($hr->fresh()->is_active);

        $this->artisan('ihr:account', ['action' => 'disable', 'login' => $hr->email, '--force' => true])->assertSuccessful();
        $this->assertFalse($hr->fresh()->is_active);
    }

    public function test_new_accounts_are_active_and_is_active_is_not_mass_assignable(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'is_active' => false, 'is_hr' => true,
            'password' => 'Kuala-Lumpur2026', 'password_confirmation' => 'Kuala-Lumpur2026',
        ]);

        $user = User::where('email', 'sneaky@example.com')->first();
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_hr);
    }

    // ── Google sign-in ──────────────────────────────────────────────

    public function test_google_sign_in_does_not_skip_the_state_check_outside_local(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andThrow(new InvalidStateException);
        $provider->shouldNotReceive('stateless');
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google/callback')->assertRedirect('/login');
        $this->assertGuest();
    }
}
