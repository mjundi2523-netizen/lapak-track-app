<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealerBill extends Model
{
    protected $table = 'dealer_bills';
    protected $primaryKey = 'dbid';

    protected $fillable = [
        'bill_id',
        'dsid',
        'total_amount',
        'due_date',
        'billing_status',
        'period_start',
        'period_end',
        'created_by',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'due_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function dealerStall(): BelongsTo
    {
        return $this->belongsTo(DealerStall::class, 'dsid', 'dsid');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DealerPayment::class, 'dbid', 'dbid');
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
        return 'dbid';
    }

    /**
     * Recalculate billing status based on actual payments.
     */
    public function recalculateBillingStatus(): void
    {
        $totalPaid = $this->payments()
            ->where('is_voided', false)
            ->sum('paid_amount');

        if ($totalPaid >= $this->total_amount) {
            $this->billing_status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->billing_status = 'installment';
        } elseif ($this->billing_status !== 'pending') {
            $this->billing_status = 'unpaid';
        }

        $this->save();
    }
}
