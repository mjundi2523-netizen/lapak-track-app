<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'market_id',
        'is_approved',
        'email_verified_at',
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
            'is_approved' => 'boolean',
        ];
    }

    /** Market (tenant) tempat user ini bernaung; null = developer/superadmin lintas market. */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class, 'market_id', 'mid');
    }

    /** Akun sudah diverifikasi developer (gerbang akses aplikasi). */
    public function isApproved(): bool
    {
        return (bool) $this->is_approved;
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
