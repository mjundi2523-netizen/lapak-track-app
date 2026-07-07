<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AddOn extends Model
{
    use BelongsToMarket;

    protected $table = 'add_ons';
    protected $primaryKey = 'aoid';

    protected $fillable = [
        'market_id',
        'add_on',
        'price',
        'frequency',
        'is_rent_date',
        'start_date',
        'created_by',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_rent_date' => 'boolean',
            'start_date' => 'date',
        ];
    }

    public function stalls(): BelongsToMany
    {
        return $this->belongsToMany(Stall::class, 'stall_add_ons', 'aoid', 'sid', 'aoid', 'sid');
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
