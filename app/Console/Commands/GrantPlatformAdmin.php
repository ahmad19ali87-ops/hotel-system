<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantPlatformAdmin extends Command
{
    protected $signature = 'platform:grant-admin {email : Email address of the existing site-owner account}';

    protected $description = 'Grant platform-wide administration to an existing account';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No account exists for that email address.');

            return self::FAILURE;
        }

        if (! $this->confirm("Grant platform-wide access to {$user->email}?")) {
            return self::SUCCESS;
        }

        $user->forceFill([
            'role' => 'super_admin',
            'hotel_id' => null,
        ])->save();

        $this->info('Platform administrator access granted.');

        return self::SUCCESS;
    }
}