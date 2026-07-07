<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringExpense extends Model
{
    use BelongsToMarket;

    protected $table = 'recurring_expenses';
    protected $primaryKey = 'rxid';

    protected $fillable = [
        'market_id',
        'ecid',
        'title',
        'amount',
        'frequency',
        'interval_count',
        'payment_method',
        'start_date',
        'auto_post',
        'is_active',
        'generated_until',
        'note',
        'created_by',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'interval_count' => 'integer',
            'start_date' => 'date',
            'generated_until' => 'date',
            'auto_post' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'ecid', 'ecid');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'rxid', 'rxid');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRouteKeyName(): string
    {
        return 'rxid';
    }
}
