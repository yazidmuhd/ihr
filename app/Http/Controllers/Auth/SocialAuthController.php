<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

class SocialAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        // Tip: prompt select_account helps when you have multiple Google accounts logged in
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            // Common local dev fallback (cookie/session domain mismatch)
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Throwable $e) {
            return redirect()->route('login')->with('status', 'Google sign-in failed. Please try again.');
        }

        $email = $googleUser->getEmail();
        if (! $email) {
            return redirect()->route('login')->with('status', 'Google account email not available.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $name = $googleUser->getName()
                ?: ($googleUser->getNickname() ?: 'Google User');

            // Create a safe unique username from email prefix
            $base = Str::slug(Str::before($email, '@'), '_');
            $username = $base ?: ('user_' . Str::random(6));
            $i = 1;

            while (User::where('username', $username)->exists()) {
                $username = ($base ?: 'user') . '_' . $i++;
            }

            $user = User::create([
                'name'              => $name,
                'email'             => $email,
                'username'          => $username,
                'role'              => 'applicant',
                'is_hr'             => false,
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'password'          => Hash::make(Str::random(40)),
            ]);
        } else {
            $dirty = false;

            if (! $user->google_id) {
                $user->google_id = $googleUser->getId();
                $dirty = true;
            }

            $avatar = $googleUser->getAvatar();
            if ($avatar && $user->avatar !== $avatar) {
                $user->avatar = $avatar;
                $dirty = true;
            }

            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
                $dirty = true;
            }

            if ($dirty) {
                $user->save();
            }
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $user->is_hr
            ? redirect()->route('hr.dashboard')
            : redirect()->route('applicant.dashboard');
    }
}
