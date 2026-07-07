<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    protected $table = 'markets';
    protected $primaryKey = 'mid';

    protected $fillable = [
        'name',
        'owner_name',
        'phone',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'market_id', 'mid');
    }

    public function getRouteKeyName(): string
    {
        return 'mid';
    }
}
