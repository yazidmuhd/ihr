<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Disable or re-enable an account without deleting it (its audit history stays).
 *
 *   php artisan ihr:account list
 *   php artisan ihr:account disable someone@company.com
 *   php artisan ihr:account enable  someone@company.com
 */
class ManageAccount extends Command
{
    protected $signature = 'ihr:account
        {action : list, disable or enable}
        {login? : e-mail or username (not needed for list)}
        {--force : allow disabling the last active HR account}';

    protected $description = 'List HR accounts, or disable / re-enable any account';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'list'    => $this->list(),
            'disable' => $this->setActive(false),
            'enable'  => $this->setActive(true),
            default   => $this->failWith('Action must be list, disable or enable.'),
        };
    }

    private function list(): int
    {
        $this->table(
            ['ID', 'Username', 'E-mail', 'Role', 'Active'],
            User::where('is_hr', true)->orderBy('id')->get()
                ->map(fn ($u) => [$u->id, $u->username, $u->email, $u->role, $u->is_active ? 'yes' : 'NO']),
        );

        return self::SUCCESS;
    }

    private function setActive(bool $active): int
    {
        $login = (string) $this->argument('login');
        $user  = User::where('email', $login)->orWhere('username', $login)->first();

        if (! $user) return $this->failWith("No account with e-mail or username \"{$login}\".");

        if (! $active && $user->is_hr && ! $this->option('force')
            && User::where('is_hr', true)->where('is_active', true)->whereKeyNot($user->id)->doesntExist()) {
            return $this->failWith('This is the last active HR account; nobody could sign in to HR. Use --force to disable it anyway.');
        }

        $user->forceFill(['is_active' => $active] + ($active ? [] : ['remember_token' => null]))->save();

        if (! $active) {
            // Sign out every open session now instead of on the next request
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $this->info(($active ? 'Enabled' : 'Disabled')." {$user->username} ({$user->email}).");

        return self::SUCCESS;
    }

    private function failWith(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }
}
