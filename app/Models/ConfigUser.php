<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigUser extends Model
{
    protected $table = 'config_users';

    protected $fillable = [
        'user_id',
        'dark_mode',
    ];

    protected function casts(): array
    {
        return [
            'dark_mode' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
