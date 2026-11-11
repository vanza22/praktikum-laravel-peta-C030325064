<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\Role;

#[Fillable(['name', 'email', 'password', 'role', 'sekolah_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

   public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN->value;
    }

    public function isDinas(): bool
    {
        return $this->role === Role::DINAS->value;
    }

    public function isSekolah(): bool
    {
        return $this->role === Role::SEKOLAH->value;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            Role::ADMIN->value => 'Admin',
            Role::DINAS->value => 'Dinas Pendidikan',
            default => 'Sekolah',
        };
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}