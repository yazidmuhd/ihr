<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    // Render register page
    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    // Handle register submit (applicants)
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'username' => $data['email'],
                'is_hr'    => false,
                'password' => Hash::make($data['password']),
            ]);

            // Create applicant record
            DB::table('applicants')->insert([
                'user_id'    => $user->id,
                'email'      => $user->email,
                'name'       => $user->name ?? '—',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // The Vue component will show the modal and redirect to login
            return back();
        });
    }
}