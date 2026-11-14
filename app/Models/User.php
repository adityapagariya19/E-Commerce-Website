<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'phone', 'role', 'is_active'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role !== 'customer' && array_key_exists($this->role, config('permissions.roles', []));
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->isAdmin() || ! $this->is_active) {
            return false;
        }
        $perms = config("permissions.roles.{$this->role}.permissions", []);
        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }

    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function addresses(): HasMany { return $this->hasMany(Address::class); }
    public function roleLabel(): string { return $this->isAdmin() ? 'Admin' : 'Customer'; }
}
