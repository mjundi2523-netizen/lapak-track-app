<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stall extends Model
{
    protected $table = 'stall';
    protected $primaryKey = 'sid';

    protected $fillable = [
        'block',
        'description',
        'ptid',
        'is_active',
        'created_by',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class, 'ptid', 'ptid');
    }

    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class, 'stall_add_ons', 'sid', 'aoid', 'sid', 'aoid');
    }

    public function dealerStalls(): HasMany
    {
        return $this->hasMany(DealerStall::class, 'sid', 'sid');
    }

    public function activeRentals(): HasMany
    {
        return $this->hasMany(DealerStall::class, 'sid', 'sid')->where('deleted', false);
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
        return 'sid';
    }
}
