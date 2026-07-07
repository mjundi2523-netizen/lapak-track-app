<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use BelongsToMarket;

    protected $table = 'expense_categories';
    protected $primaryKey = 'ecid';

    protected $fillable = [
        'market_id',
        'name',
        'created_by',
        'modified_by',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'ecid', 'ecid');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRouteKeyName(): string
    {
        return 'ecid';
    }
}
