<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealerStall extends Model
{
    use BelongsToMarket;

    protected $table = 'dealer_stall';
    protected $primaryKey = 'dsid';

    protected $fillable = [
        'market_id',
        'did',
        'sid',
        'rent_start_date',
        'rent_end_date',
        'deleted',
        'created_by',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'rent_start_date' => 'date',
            'rent_end_date' => 'date',
            'deleted' => 'boolean',
        ];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'did', 'did');
    }

    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class, 'sid', 'sid');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(DealerBill::class, 'dsid', 'dsid');
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
