<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use App\Models\Concerns\HasObfuscatedId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends Model
{
    use BelongsToMarket, HasObfuscatedId;

    protected $table = 'incomes';
    protected $primaryKey = 'imid';

    protected $fillable = [
        'market_id',
        'icid',
        'title',
        'amount',
        'income_date',
        'payment_method',
        'note',
        'is_voided',
        'voided_reason',
        'voided_at',
        'voided_by',
        'created_by',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'income_date' => 'date',
            'is_voided' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'icid', 'icid');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRouteKeyName(): string
    {
        return 'imid';
    }
}
