<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentTerm extends Model
{
    protected $table = 'payment_terms';
    protected $primaryKey = 'ptid';

    protected $fillable = [
        'term_name',
        'frequency',
        'interval_count',
        'is_new',
        'price',
        'created_by',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'interval_count' => 'integer',
            'is_new' => 'boolean',
        ];
    }

    public function stalls(): HasMany
    {
        return $this->hasMany(Stall::class, 'ptid', 'ptid');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
