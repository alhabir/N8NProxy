<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateFirstAdmin extends Command
{
    protected $signature = 'app:create-first-admin {--email=info@n8ndesigner.com} {--password=}';

    protected $description = 'Create or update the primary admin account for production environments';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $password = $this->option('password') ?: '119115Ab30772';

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->forceFill([
                'name' => $user->name ?: 'Primary Admin',
                'password' => Hash::make($password),
                'is_admin' => true,
            ])->save();

            $this->info(sprintf('Admin user %s updated and flagged as admin.', $email));
        } else {
            $user = User::create([
                'name' => 'Primary Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
            ]);

            $this->info(sprintf('Admin user %s created.', $user->email));
        }

        return self::SUCCESS;
    }
}
