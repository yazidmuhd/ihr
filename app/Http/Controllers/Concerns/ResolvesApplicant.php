<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The applicant profile (applicants row) of the signed-in user.
 * Profiles are linked by applicants.user_id (unique), never by e-mail,
 * so one account can never pick up another person's applications.
 */
trait ResolvesApplicant
{
    protected function resolveApplicantId(bool $createIfMissing = false): ?int
    {
        $user = Auth::user();
        if (!$user) return null;

        $id = DB::table('applicants')->where('user_id', $user->id)->value('id');
        if ($id) return (int) $id;

        if (!$createIfMissing) return null;

        DB::table('applicants')->insertOrIgnore([
            'user_id'    => $user->id,
            'name'       => $user->name ?: $user->email,
            'email'      => $user->email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('applicants')->where('user_id', $user->id)->value('id');
    }
}
