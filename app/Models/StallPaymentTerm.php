<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StallPaymentTerm extends Model
{
    use BelongsToMarket;

    protected $table = 'stall_payment_terms';
    protected $primaryKey = 'sptid';

    protected $fillable = [
        'market_id',
        'sid',
        'ptid',
        'created_by',
        'modified_by',
    ];

    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class, 'sid', 'sid');
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class, 'ptid', 'ptid');
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
