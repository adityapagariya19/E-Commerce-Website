<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'jbo:create-admin {email} {--name=Administrator} {--role=super_admin} {--password=}';
    protected $description = 'Create (or reset) an admin user. Roles: super_admin, store_manager, content_manager, order_support.';

    public function handle(): int
    {
        $role = $this->option('role');
        if (! array_key_exists($role, config('permissions.roles'))) {
            $this->error('Unknown role. Use one of: '.implode(', ', array_keys(config('permissions.roles'))));
            return self::FAILURE;
        }
        $password = $this->option('password') ?: $this->secret('Password');
        $v = Validator::make(['email' => $this->argument('email'), 'password' => $password], [
            'email' => ['required', 'email'], 'password' => ['required', 'string', 'min:3'],
        ]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) { $this->error($e); }
            return self::FAILURE;
        }
        $user = User::updateOrCreate(['email' => $this->argument('email')], ['name' => $this->option('name'), 'password' => $password, 'role' => $role, 'is_active' => true]);
        $this->info("Admin ready: {$user->email} ({$role}). Log in at /admin/login");
        return self::SUCCESS;
    }
}
