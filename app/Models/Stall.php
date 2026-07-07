<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Stall extends Model
{
    use BelongsToMarket;

    protected $table = 'stall';
    protected $primaryKey = 'sid';

    protected $fillable = [
        'market_id',
        'block',
        'number',
        'description',
        'size',
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

    /** Kode lokasi gabungan: "{block} / {number}" (mis. "A01 / 05"). */
    public function getCodeAttribute(): string
    {
        return trim(($this->block ?? '') . ' / ' . ($this->number ?? ''));
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

    /**
     * Rental yang sedang MENGISI lapak (occupancy): rental tidak dihapus DAN
     * hari ini berada di dalam window sewa (rent_start <= hari ini <= rent_end / ongoing).
     * Catatan: `deleted` = aktif/tidak-aktif record rental (terpisah dari occupancy).
     */
    public function activeRentals(): HasMany
    {
        $today = Carbon::today()->toDateString();

        // Eksklusif di ujung: pada hari rent_end_date lapak sudah dianggap kosong.
        return $this->hasMany(DealerStall::class, 'sid', 'sid')
            ->where('deleted', false)
            ->whereDate('rent_start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('rent_end_date')
                    ->orWhereDate('rent_end_date', '>', $today);
            });
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
