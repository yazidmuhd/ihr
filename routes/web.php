<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthController;

use App\Http\Controllers\VacancyController;
use App\Http\Controllers\ResumeController;

use App\Http\Controllers\Applicant\JobBrowserController;
use App\Http\Controllers\Applicant\ProfileController;
use App\Http\Controllers\Applicant\ApplicationsController;
use App\Http\Controllers\Applicant\InterviewController as ApplicantInterviewController;
use App\Http\Controllers\Applicant\DashboardController as ApplicantDashboardController;

use App\Http\Controllers\HR\DashboardController as HRDashboardController;
use App\Http\Controllers\HR\AiRankingController;
use App\Http\Controllers\HR\ShortlistController;
use App\Http\Controllers\HR\InterviewController as HRInterviewController;
use App\Http\Controllers\HR\EmployeeController as HREmployeeController;

use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Home (redirect by role)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->is_hr
            ? redirect()->route('hr.dashboard')
            : redirect()->route('applicant.dashboard');
    }

    return redirect()->route('login');
})->name('home');

/*
|--------------------------------------------------------------------------
| Quick DB debug (optional)
|--------------------------------------------------------------------------
*/
Route::get('/debug/db', function () {
    try {
        $name    = config('database.default');
        $dbInfo  = DB::select('select current_user, current_database(), inet_server_addr() as host, inet_server_port() as port, now() as server_time');
        $version = DB::select('select version() as ver');

        return response()->json([
            'ok'         => true,
            'connection' => $name,
            'server'     => $dbInfo[0] ?? null,
            'version'    => $version[0]->ver ?? null,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok'    => false,
            'error' => $e->getMessage(),
            'env'   => [
                'DB_CONNECTION' => env('DB_CONNECTION'),
                'DB_HOST'       => env('DB_HOST'),
                'DB_PORT'       => env('DB_PORT'),
                'DB_DATABASE'   => env('DB_DATABASE'),
                'DB_USERNAME'   => env('DB_USERNAME'),
            ],
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| Auth (guest only)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {

    // Login
    Route::get('/login',  [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    // Register
    Route::get('/register',  [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    // Password Reset
    Route::get('/password/request',       [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/password/email',        [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/password/reset/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/password/update',       [NewPasswordController::class, 'store'])->name('password.update');

    // Google OAuth
    Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirect'])->name('oauth.google.redirect');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])->name('oauth.google.callback');
});

/*
|--------------------------------------------------------------------------
| Logout (auth required)
|--------------------------------------------------------------------------
*/
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Notifications (auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
});

/*
|--------------------------------------------------------------------------
| Public jobs
|--------------------------------------------------------------------------
*/
Route::get('/jobs', [JobBrowserController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{id}', [JobBrowserController::class, 'show'])->whereNumber('id')->name('jobs.show');

Route::get('/applicant/jobs', fn () => redirect()->route('jobs.index'))->name('applicant.jobs.index');
Route::get('/applicant/jobs/{id}', fn ($id) => redirect()->route('jobs.show', $id))
    ->whereNumber('id')
    ->name('applicant.jobs.show');

/*
|--------------------------------------------------------------------------
| Applicant area (auth + role:applicant)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:applicant'])->group(function () {

    Route::get('/applicant/dashboard', [ApplicantDashboardController::class, 'index'])->name('applicant.dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('applicant.profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('applicant.profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('applicant.profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('applicant.profile.avatar.delete');

    Route::get('/applications', [ApplicationsController::class, 'index'])->name('app.applications.index');
    Route::delete('/applications/{id}', [ApplicationsController::class, 'withdraw'])->whereNumber('id')->name('app.applications.withdraw');

    Route::post('/applications', [JobBrowserController::class, 'apply'])->name('app.applications.store');

    Route::prefix('app')->group(function () {

        Route::get('/interviews', [ApplicantInterviewController::class, 'index'])->name('app.interviews.index');
        Route::post('/interviews/{id}/respond', [ApplicantInterviewController::class, 'respond'])
            ->whereNumber('id')
            ->name('app.interviews.respond');

        Route::get('/resume', [ResumeController::class, 'index'])->name('app.resume.index');
        Route::post('/resume', [ResumeController::class, 'store'])->name('app.resume.store');
        Route::post('/resume/reorder', [ResumeController::class, 'reorder'])->name('app.resume.reorder');

        Route::patch('/resume/{id}/activate', [ResumeController::class, 'activate'])->whereNumber('id')->name('app.resume.activate');
        Route::delete('/resume/{id}', [ResumeController::class, 'destroy'])->whereNumber('id')->name('app.resume.destroy');
        Route::post('/resume/{id}/retry', [ResumeController::class, 'retry'])->whereNumber('id')->name('app.resume.retry');
    });
});

/*
|--------------------------------------------------------------------------
| HR area (auth + role:hr)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:hr'])->group(function () {

    Route::get('/hr/dashboard', [HRDashboardController::class, 'index'])->name('hr.dashboard');

    Route::get('/hr/vacancies', [VacancyController::class, 'index'])->name('hr.vacancies.index');
    Route::get('/hr/vacancies/create', [VacancyController::class, 'create'])->name('hr.vacancies.create');
    Route::post('/hr/vacancies', [VacancyController::class, 'store'])->name('hr.vacancies.store');
    Route::get('/hr/vacancies/{id}/edit', [VacancyController::class, 'edit'])->whereNumber('id')->name('hr.vacancies.edit');
    Route::put('/hr/vacancies/{id}', [VacancyController::class, 'update'])->whereNumber('id')->name('hr.vacancies.update');
    Route::delete('/hr/vacancies/{id}', [VacancyController::class, 'destroy'])->whereNumber('id')->name('hr.vacancies.destroy');
    Route::patch('/hr/vacancies/{id}/status', [VacancyController::class, 'setStatus'])->whereNumber('id')->name('hr.vacancies.status');

    Route::get('/hr/vacancies/{id}/ai', [AiRankingController::class, 'index'])->whereNumber('id')->name('hr.vacancies.ai');
    Route::post('/hr/vacancies/{id}/ai/rescore', [AiRankingController::class, 'rescoreAll'])->whereNumber('id')->name('hr.vacancies.ai.rescore');
    Route::post('/hr/vacancies/{id}/ai/shortlist', [AiRankingController::class, 'shortlist'])->whereNumber('id')->name('hr.vacancies.ai.shortlist');

    Route::patch('/hr/applications/{id}/status', [AiRankingController::class, 'setStatus'])->whereNumber('id')->name('hr.applications.status');

    Route::get('/hr/shortlist', [ShortlistController::class, 'vacancies'])->name('hr.shortlist');
    Route::get('/hr/shortlist/vacancy/{id}', [ShortlistController::class, 'byVacancy'])->whereNumber('id')->name('hr.shortlist.vacancy');
    Route::post('/hr/applications/{id}/decision', [ShortlistController::class, 'decision'])->whereNumber('id')->name('hr.applications.decision');

    Route::get('/hr/interviews', [HRInterviewController::class, 'index'])->name('hr.interviews.index');
    Route::get('/hr/evaluations', [HRInterviewController::class, 'index'])->name('hr.evaluations');
    Route::get('/hr/vacancies/{id}/evaluation', [HRInterviewController::class, 'vacancy'])->whereNumber('id')->name('hr.vacancies.evaluation');

    Route::post('/hr/interviews/upsert', [HRInterviewController::class, 'upsert'])->name('hr.interviews.upsert');
    Route::post('/hr/interviews/{id}/details', [HRInterviewController::class, 'updateDetails'])->whereNumber('id')->name('hr.interviews.details');
    Route::patch('/hr/interviews/{id}/status', [HRInterviewController::class, 'status'])->whereNumber('id')->name('hr.interviews.status');

    Route::post('/hr/interviews/{id}/panels', [HRInterviewController::class, 'panels'])->whereNumber('id')->name('hr.interviews.panels');
    Route::post('/hr/interviews/{id}/rate', [HRInterviewController::class, 'rate'])->whereNumber('id')->name('hr.interviews.rate');
    Route::patch('/hr/interviews/{id}/finalize', [HRInterviewController::class, 'finalize'])->whereNumber('id')->name('hr.interviews.finalize');

    Route::post('/hr/interviews/{id}/hire', [HRInterviewController::class, 'hire'])->whereNumber('id')->name('hr.interviews.hire');

    Route::get('/hr/employees', [HREmployeeController::class, 'index'])->name('hr.employees.index');
    Route::get('/hr/employees/{id}', [HREmployeeController::class, 'show'])->whereNumber('id')->name('hr.employees.show');
});

/*
|--------------------------------------------------------------------------
| AI debug (optional)
|--------------------------------------------------------------------------
*/
Route::get('/debug/ai', function () {
    try {
        return response()->json(\App\Services\AiClient::ping());
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
})->middleware('auth');
