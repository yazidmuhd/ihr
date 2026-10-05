<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Creates (or updates) the first HR account from environment variables:
 *   HR_ADMIN_USERNAME, HR_ADMIN_EMAIL, HR_ADMIN_PASSWORD
 * The development default password is only allowed locally.
 */
class HrUserSeeder extends Seeder
{
    public function run(): void
    {
        $username = env('HR_ADMIN_USERNAME', 'humanres');
        $email    = env('HR_ADMIN_EMAIL', 'humanres@ihr.local');
        $password = env('HR_ADMIN_PASSWORD');

        // Any internet-facing environment (staging, production) needs a real password
        if (! app()->environment('local', 'testing') && (! $password || strlen($password) < 12)) {
            throw new RuntimeException('Set HR_ADMIN_PASSWORD (at least 12 characters) before seeding ' . app()->environment() . '.');
        }

        $user = User::firstOrNew(['username' => $username]);

        // Only set a password when the account is first created, so re-seeding
        // a live database never resets a password the admin has changed.
        if (! $user->exists) {
            $user->name              = 'HR Administrator';
            $user->email             = $email;
            $user->password          = Hash::make($password ?: 'admin01');
            $user->email_verified_at = now();
        }

        $user->role  = 'hr';
        $user->is_hr = true;
        $user->save();
    }
}
