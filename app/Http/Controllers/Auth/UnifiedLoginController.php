<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UnifiedLoginController extends Controller
{
    public function create(): Response
    {
        // Reuse your existing Vue login page (Auth/Login.vue)
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request)
    {
        $creds = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool) ($creds['remember'] ?? false);

        // 1) Try HR guard
        if (Auth::guard('hr')->attempt([
            'username' => $creds['username'],
            'password' => $creds['password']
        ], $remember)) {
            $request->session()->regenerate();
            return redirect()->intended('/hr/dashboard');
        }

        // 2) Try Applicant guard
        if (Auth::guard('applicant')->attempt([
            'username' => $creds['username'],
            'password' => $creds['password']
        ], $remember)) {
            $request->session()->regenerate();
            return redirect()->intended('/applicant/dashboard');
        }

        throw ValidationException::withMessages([
            'username' => __('These credentials do not match our records.'),
        ]);
    }

    public function destroy(Request $request)
    {
        // log out from whichever guard is authenticated
        if (Auth::guard('hr')->check()) {
            Auth::guard('hr')->logout();
        }
        if (Auth::guard('applicant')->check()) {
            Auth::guard('applicant')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
