<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_hr_logs_in_with_username_and_lands_on_hr_dashboard(): void
    {
        $hr = User::factory()->hr()->create(['username' => 'hr.staff']);

        $this->post('/login', ['login' => 'hr.staff', 'password' => 'password'])
            ->assertRedirect(route('hr.dashboard'));

        $this->assertAuthenticatedAs($hr);
    }

    public function test_applicant_logs_in_with_email_and_lands_on_applicant_dashboard(): void
    {
        [$user] = $this->applicant();

        $this->post('/login', ['login' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('applicant.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        [$user] = $this->applicant();

        $this->post('/login', ['login' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_registration_creates_an_applicant_account_and_profile(): void
    {
        $this->post('/register', [
            'name'                  => 'Aina Rahman',
            'email'                 => 'aina@example.com',
            'password'              => 'Password1234!',
            'password_confirmation' => 'Password1234!',
        ])->assertRedirect();

        $user = User::where('email', 'aina@example.com')->firstOrFail();
        $this->assertSame('applicant', $user->role);
        $this->assertFalse($user->is_hr);
        $this->assertDatabaseHas('applicants', ['user_id' => $user->id, 'email' => 'aina@example.com']);
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        $this->applicant(['email' => 'taken@example.com']);

        $this->post('/register', [
            'name'                  => 'Someone',
            'email'                 => 'taken@example.com',
            'password'              => 'Password1234!',
            'password_confirmation' => 'Password1234!',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, DB::table('users')->where('email', 'taken@example.com')->count());
    }

    public function test_logout(): void
    {
        [$user] = $this->applicant();

        $this->actingAs($user)->post('/logout');

        $this->assertGuest();
    }
}
