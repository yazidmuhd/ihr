<?php

// app/Http/Controllers/Applicant/ProfileController.php
namespace App\Http\Controllers\Applicant;

use App\Services\Uploads;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        $u = Auth::user();
        return Inertia::render('Applicant/Profile/Edit', [
            'user' => [
                'id'       => $u->id,
                'name'     => $u->name,
                'username' => $u->username,
                'email'    => $u->email,
                'phone'    => $u->phone,
                'about'    => $u->about,
                // expose a public URL if avatar exists
                'avatarUrl'=> Uploads::url($u->avatar_path),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $u = Auth::user();
        $data = $request->validate([
            'name'     => ['nullable','string','max:120'],
            'username' => ['nullable','string','max:60', Rule::unique('users','username')->ignore($u->id)],
            'phone'    => ['nullable','string','max:40'],
            'about'    => ['nullable','string','max:2000'],
        ]);
        $u->fill($data)->save();
        return back()->with('status','Profile updated');
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ]);

        $u = Auth::user();

        // store the new file first, then remove the old one
        $old = $u->avatar_path;
        $u->avatar_path = Uploads::store($request->file('avatar'), 'avatars');
        $u->save();
        Uploads::delete($old);

        return back()->with('status','Avatar updated');
    }

    public function deleteAvatar()
    {
        $u = Auth::user();
        if ($u->avatar_path) {
            $old = $u->avatar_path;
            $u->avatar_path = null;
            $u->save();
            Uploads::delete($old);
        }
        return back()->with('status','Avatar removed');
    }
}
