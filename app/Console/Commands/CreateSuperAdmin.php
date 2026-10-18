<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Create (or promote) an EazyPOS team account that can open /admin.
 */
class CreateSuperAdmin extends Command
{
    protected $signature = 'eazypos:create-admin {email} {--name=EazyPOS Team} {--password= : Leave empty to be asked securely}';

    protected $description = 'Create an EazyPOS team (super admin) account for the Team Console';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        $password = (string) ($this->option('password') ?: ($user ? '' : $this->secret('Password (at least 8 characters)')));

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => $user ? ['nullable', Password::min(8)] : ['required', Password::min(8)]],
        );
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        if ($user) {
            $user->forceFill(['is_super_admin' => true] + ($password !== '' ? ['password' => $password] : []))->save();
            $this->info("{$email} is now an EazyPOS team administrator.");

            return self::SUCCESS;
        }

        $user = User::query()->create(['name' => (string) $this->option('name'), 'email' => $email, 'password' => $password]);
        $user->forceFill(['is_super_admin' => true, 'email_verified_at' => now()])->save();
        $this->info("Team administrator {$email} created. Sign in on the home page to open the Team Console.");

        return self::SUCCESS;
    }
}
