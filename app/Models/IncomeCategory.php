<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use App\Models\Concerns\HasObfuscatedId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomeCategory extends Model
{
    use BelongsToMarket, HasObfuscatedId;

    protected $table = 'income_categories';
    protected $primaryKey = 'icid';

    protected $fillable = [
        'market_id',
        'name',
        'created_by',
        'modified_by',
    ];

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class, 'icid', 'icid');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRouteKeyName(): string
    {
        return 'icid';
    }
}
