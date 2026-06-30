<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_premium' => 'boolean',
        ];
    }

    /** Akun premium → membuka fitur yang digerbang (laporan, denah, pengeluaran, eksternal). */
    public function isPremium(): bool
    {
        return (bool) $this->is_premium;
    }

    /** Pengaturan per-user (dark mode, dll). */
    public function config(): HasOne
    {
        return $this->hasOne(ConfigUser::class);
    }

    /** Preferensi mode gelap (disimpan di config_users). */
    public function prefersDark(): bool
    {
        return (bool) ($this->config?->dark_mode);
    }
}
