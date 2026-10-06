<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'admin:create {email} {--name=}';

    protected $description = 'Create an initial Super Admin using a privately entered password';

    public function handle(): int
    {
        $data = [
            'email' => strtolower(trim((string) $this->argument('email'))),
            'name' => $this->option('name') ?: $this->ask('Administrator name'),
            'password' => $this->secret('Password (at least 12 characters, letters and numbers)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];
        $validator = Validator::make($data, [
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        if (! Role::where('slug', Role::SUPER_ADMIN)->exists()) {
            $this->error('Run database seeding to provision access-control roles first.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'user_type' => User::TYPE_STAFF,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'password_changed_at' => now(),
            ]);
            $user->assignRole(Role::SUPER_ADMIN);
        });
        $this->info('Administrator created.');

        return self::SUCCESS;
    }
}
