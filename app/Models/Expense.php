<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToMarket;

    protected $table = 'expenses';
    protected $primaryKey = 'xpid';

    protected $fillable = [
        'market_id',
        'ecid',
        'rxid',
        'title',
        'amount',
        'expense_date',
        'payment_method',
        'note',
        'status',
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
            'expense_date' => 'date',
            'is_voided' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'ecid', 'ecid');
    }

    public function recurring(): BelongsTo
    {
        return $this->belongsTo(RecurringExpense::class, 'rxid', 'rxid');
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
        return 'xpid';
    }
}
