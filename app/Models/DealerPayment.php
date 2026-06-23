<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerPayment extends Model
{
    protected $table = 'dealer_payment';
    protected $primaryKey = 'dpid';

    protected $fillable = [
        'bill_id',
        'dbid',
        'paid_amount',
        'payment_date',
        'payment_method',
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
            'paid_amount' => 'decimal:2',
            'payment_date' => 'date',
            'is_voided' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }

    public function dealerBill(): BelongsTo
    {
        return $this->belongsTo(DealerBill::class, 'dbid', 'dbid');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function getRouteKeyName(): string
    {
        return 'dpid';
    }
}
