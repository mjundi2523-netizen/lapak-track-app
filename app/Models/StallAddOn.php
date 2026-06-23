<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StallAddOn extends Model
{
    protected $table = 'stall_add_ons';
    protected $primaryKey = 'saoid';

    protected $fillable = [
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
