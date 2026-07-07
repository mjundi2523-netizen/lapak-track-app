<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StallAddOn extends Model
{
    use BelongsToMarket;

    protected $table = 'stall_add_ons';
    protected $primaryKey = 'saoid';

    protected $fillable = [
        'market_id',
        'sid',
        'aoid',
        'created_by',
        'modified_by',
    ];

    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class, 'sid', 'sid');
    }

    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class, 'aoid', 'aoid');
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
