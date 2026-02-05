<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        // Prefer Laravel/Vite's manifest hash. Fallback to md5 of manifest.
        try {
            if (method_exists(Vite::class, 'manifestHash')) {
                return Vite::manifestHash();
            }
        } catch (\Throwable $e) {
            // ignore and fallback
        }

        $manifest = public_path('build/manifest.json');
        return is_file($manifest)
            ? md5_file($manifest)
            : parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            // Auth user info
            'auth' => [
    'user' => $request->user()
        ? [
            'id'        => $request->user()->id,
            'name'      => $request->user()->name,
            'is_hr'     => $request->user()->is_hr ?? false,
            'email'     => $request->user()->email ?? null,
            'avatarUrl' => $request->user()->avatar_path
                ? Storage::disk('public')->url($request->user()->avatar_path)
                : null,
        ]
        : null,
],


            // Flash messages
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'errors' => fn () => $request->session()->get('errors')
                    ? $request->session()->get('errors')->getBag('default')->getMessages()
                    : null,
            ],

            // 🔔 Global notification counters (used in nav + dashboard + floating pill)
            'notifications' => fn () => $this->buildNotifications($request),
        ]);
    }

    /**
     * Build notification counters for HR and Applicant.
     *
     * - HR:
     *    pending_interview_actions = interviews where candidate has responded
     *    (accepted / declined) → HR should review/update status.
     *
     * - Applicant:
     *    pending_interviews = interviews for this applicant where
     *    candidate_status IS NULL → they haven’t confirmed yet.
     */
    protected function buildNotifications(Request $request): array
    {
        $user = $request->user();

        // Default predictable structure
        $data = [
            'hr' => [
                'pending_interview_actions' => 0,
            ],
            'applicant' => [
                'pending_interviews' => 0,
            ],
        ];

        if (!$user) {
            return $data;
        }

        // HR side notifications
        if ($user->is_hr ?? false) {
            $hrPending = DB::table('interviews')
                ->whereIn('candidate_status', ['accepted', 'declined'])
                ->count();

            $data['hr']['pending_interview_actions'] = $hrPending;
            return $data;
        }

        // Applicant side notifications
        $pendingInterviews = DB::table('interviews as i')
            ->join('applications as a', 'a.id', '=', 'i.application_id')
            ->where('a.applicant_id', $user->id) // 🔑 same pattern as your InterviewController
            ->whereNull('i.candidate_status')    // not yet responded
            ->count();

        $data['applicant']['pending_interviews'] = $pendingInterviews;

        return $data;
    }
}
