<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalDealer extends Model
{
    use BelongsToMarket;

    protected $table = 'external_dealers';
    protected $primaryKey = 'edid';

    protected $fillable = [
        'market_id',
        'did',
        'ptid',
        'start_date',
        'end_date',
        'deleted',
        'created_by',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'deleted' => 'boolean',
        ];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'did', 'did');
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class, 'ptid', 'ptid');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(DealerBill::class, 'edid', 'edid');
    }
}
