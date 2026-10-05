<?php

namespace App\Console\Commands;

use App\Services\Uploads;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Health check for an environment: run after deploying or after rotating a secret.
 * Every check is read-only or cleans up after itself, and no secret value is printed.
 *
 *   php artisan ihr:check            all checks
 *   php artisan ihr:check --only=ai  one check (app, network, database, uploads, queue, ai, mail, google)
 */
class CheckServices extends Command
{
    protected $signature = 'ihr:check {--only= : run a single check}';

    protected $description = 'Check configuration and external services (database, uploads, queue, AI, mail, Google sign-in)';

    private bool $failed = false;

    public function handle(): int
    {
        $checks = ['app', 'network', 'database', 'uploads', 'queue', 'ai', 'mail', 'google'];
        $only = $this->option('only');

        if ($only && !in_array($only, $checks, true)) {
            $this->error('Unknown check. Use one of: ' . implode(', ', $checks));
            return self::INVALID;
        }

        $this->line('Environment: <info>' . app()->environment() . '</info>');

        foreach ($only ? [$only] : $checks as $check) {
            try {
                $this->{'check' . ucfirst($check)}();
            } catch (\Throwable $e) {
                $this->result('FAIL', ucfirst($check), Str::limit($e->getMessage(), 160));
            }
        }

        $this->newLine();
        $this->line($this->failed ? '<error> Some checks failed. </error>' : '<info>All checks passed.</info>');

        return $this->failed ? self::FAILURE : self::SUCCESS;
    }

    private function checkApp(): void
    {
        $prod = app()->environment('production', 'staging');   // internet-facing

        $this->result(config('app.key') ? 'PASS' : 'FAIL', 'APP_KEY', config('app.key') ? 'set' : 'missing — run php artisan key:generate');
        $this->result($prod && config('app.debug') ? 'FAIL' : 'PASS', 'APP_DEBUG', config('app.debug') ? 'on' : 'off');

        $https = str_starts_with((string) config('app.url'), 'https://');
        $this->result(!$https && $prod ? 'FAIL' : 'PASS', 'APP_URL', config('app.url'));
    }

    private function checkNetwork(): void
    {
        $hosted   = app()->environment('production', 'staging');
        $header   = config('network.client_ip_header');
        $list     = config('network.allowlist', []);
        $scope    = config('network.allowlist_scope');

        $this->result($hosted && !$header ? 'FAIL' : 'PASS', 'Client IP header',
            $header ?: 'none (direct connections)' . ($hosted ? ' — set CLIENT_IP_HEADER=X-Real-IP on Railway' : ''));

        if (!$list) {
            $this->result('PASS', 'IP allowlist', 'off — site is public');
            return;
        }

        $invalid = array_filter($list, function ($entry) {
            [$ip, $bits] = array_pad(explode('/', $entry, 2), 2, null);
            $max = str_contains($ip, ':') ? 128 : 32;
            return !filter_var($ip, FILTER_VALIDATE_IP)
                || ($bits !== null && (!ctype_digit($bits) || (int) $bits > $max));
        });
        $tooBroad = array_filter($list, fn ($e) => preg_match('#/(\d+)$#', $e, $m) && (int) $m[1] < (str_contains($e, ':') ? 32 : 16));

        $status = $invalid ? 'FAIL' : ($tooBroad || !in_array($scope, ['all', 'hr'], true) ? 'WARN' : 'PASS');
        $detail = count($list) . " entr" . (count($list) === 1 ? 'y' : 'ies') . ", scope \"{$scope}\""
            . ($invalid ? '; invalid: ' . implode(', ', $invalid) : '')
            . ($tooBroad ? '; very wide range: ' . implode(', ', $tooBroad) : '');
        $this->result($status, 'IP allowlist', $detail);
    }

    private function checkDatabase(): void
    {
        DB::select('select 1');
        $this->result('PASS', 'Database connection', DB::getDriverName() . ' / ' . DB::connection()->getDatabaseName());

        $pending = collect(app('migrator')->getMigrationFiles(database_path('migrations')))->keys()
            ->diff(DB::table('migrations')->pluck('migration'))->count();
        $this->result($pending ? 'FAIL' : 'PASS', 'Migrations', $pending ? "{$pending} pending — run php artisan migrate" : 'up to date');

        if (DB::getDriverName() === 'pgsql') {
            $triggers = DB::table('information_schema.triggers')->where('trigger_schema', DB::raw('current_schema()'))
                ->distinct()->count('trigger_name');
            $this->result($triggers >= 8 ? 'PASS' : 'FAIL', 'Audit triggers', "{$triggers} installed");
        }
    }

    private function checkUploads(): void
    {
        $disk = Uploads::diskName();
        $path = 'healthchecks/' . Str::uuid() . '.txt';

        Uploads::disk()->put($path, 'ok');
        $readBack = Uploads::disk()->get($path);
        $url = Uploads::url($path, 5);
        Uploads::delete($path);

        $this->result($readBack === 'ok' ? 'PASS' : 'FAIL', 'Uploads storage', "disk \"{$disk}\": write, read, link, delete");

        if (app()->environment('production', 'staging') && config("filesystems.disks.{$disk}.driver") === 'local') {
            $this->result('WARN', 'Uploads persistence', 'local disk: files are lost on redeploy — set UPLOADS_DISK=s3');
        } elseif ($url && !str_contains($url, 'signature=') && !str_contains($url, 'Signature=') && $disk !== 'public') {
            $this->result('WARN', 'Uploads privacy', 'links are not signed');
        }
    }

    private function checkQueue(): void
    {
        $connection = config('queue.default');

        if ($connection === 'sync') {
            $this->result(app()->environment('production') ? 'WARN' : 'PASS', 'Queue', 'sync — jobs run inside web requests (no worker needed)');
            return;
        }

        $pending = DB::table('jobs')->count();
        $failed  = DB::table('failed_jobs')->count();
        $stale   = DB::table('jobs')->where('available_at', '<', now()->subMinutes(10)->timestamp)->whereNull('reserved_at')->count();

        $status = $stale ? 'FAIL' : ($failed ? 'WARN' : 'PASS');
        $detail = "{$connection}: {$pending} waiting, {$failed} failed" . ($stale ? ", {$stale} waiting >10 min — is the worker running?" : '');
        $this->result($status, 'Queue', $detail);
    }

    private function checkAi(): void
    {
        $key = config('services.ai.key');
        if (!$key) {
            $this->result('FAIL', 'AI API key', 'not set (AI_API_KEY)');
            return;
        }

        $base = rtrim((string) config('services.ai.base_url'), '/');
        $isOpenRouter = str_contains($base, 'openrouter.ai');

        // OpenRouter: /key validates the key for free. OpenAI-compatible: /models requires a valid key.
        $res = Http::withToken($key)->timeout(15)->get($base . ($isOpenRouter ? '/key' : '/models'));

        if ($res->successful()) {
            $limit = $isOpenRouter ? data_get($res->json(), 'data.limit') : null;
            $this->result('PASS', 'AI API key', 'accepted by ' . parse_url($base, PHP_URL_HOST)
                . ($isOpenRouter ? ($limit === null ? ' — WARNING: no spending limit on this key' : " — spend limit {$limit}") : ''));
        } else {
            $this->result('FAIL', 'AI API key', 'rejected (HTTP ' . $res->status() . ')');
        }
    }

    private function checkMail(): void
    {
        $mailer = config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->result(app()->environment('production') ? 'FAIL' : 'PASS', 'Mail', "{$mailer} — e-mails are not delivered");
            return;
        }

        $transport = Mail::mailer()->getSymfonyTransport();
        if (method_exists($transport, 'start')) {
            $transport->start();   // connects and authenticates, sends nothing
            $transport->stop();
        }
        $this->result('PASS', 'Mail', "{$mailer} — login to " . config("mail.mailers.{$mailer}.host", 'service') . ' accepted');
    }

    private function checkGoogle(): void
    {
        $id = config('services.google.client_id');
        $secret = config('services.google.client_secret');
        $redirect = (string) config('services.google.redirect');

        if (!$id || !$secret) {
            $this->result('WARN', 'Google sign-in', 'not configured (GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET)');
            return;
        }

        $sameHost = parse_url($redirect, PHP_URL_HOST) === parse_url((string) config('app.url'), PHP_URL_HOST);
        $this->result($sameHost ? 'PASS' : 'FAIL', 'Google sign-in', $sameHost
            ? "configured; redirect {$redirect} (must also be listed in Google Cloud Console)"
            : "GOOGLE_REDIRECT_URI host does not match APP_URL ({$redirect})");
    }

    private function result(string $status, string $name, string $detail): void
    {
        if ($status === 'FAIL') $this->failed = true;

        $tag = ['PASS' => '<info>PASS</info>', 'WARN' => '<comment>WARN</comment>', 'FAIL' => '<error>FAIL</error>'][$status];
        $this->line(sprintf('  %s  %-20s %s', $tag, $name, $detail));
    }
}
