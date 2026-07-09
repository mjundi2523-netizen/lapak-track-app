<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use App\Models\Concerns\HasObfuscatedId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Dealer extends Model
{
    use BelongsToMarket, HasObfuscatedId;

    protected $table = 'dealer';
    protected $primaryKey = 'did';

    protected $fillable = [
        'market_id',
        'nik',
        'name',
        'birth_date',
        'address',
        'phone_number_1',
        'phone_number_2',
        'product_type',
        'status',
        'dealer_condition',
        'scan_id',
        'letter_no',
        'created_by',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function dealerStalls(): HasMany
    {
        return $this->hasMany(DealerStall::class, 'did', 'did');
    }

    /** Langganan eksternal (pedagang non-lapak): relasi langsung ke payment_terms. */
    public function externalDealers(): HasMany
    {
        return $this->hasMany(ExternalDealer::class, 'did', 'did');
    }

    /** Langganan eksternal yang masih berjalan (belum berakhir & tidak dihapus). */
    public function activeExternal(): HasMany
    {
        $today = Carbon::today()->toDateString();

        return $this->hasMany(ExternalDealer::class, 'did', 'did')
            ->where('deleted', false)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>', $today);
            });
    }

    public function activeStalls(): HasMany
    {
        return $this->hasMany(DealerStall::class, 'did', 'did')->where('deleted', false);
    }

    /**
     * Rental yang masih berjalan: tidak dihapus DAN belum berakhir (rent_end NULL / >= hari ini).
     * Dipakai untuk: blokir nonaktifkan pedagang & tentukan boleh pilih lapak lagi di Edit.
     */
    public function activeRentals(): HasMany
    {
        $today = Carbon::today()->toDateString();

        // Eksklusif di ujung: rental yang rent_end_date-nya = hari ini sudah dianggap berakhir.
        return $this->hasMany(DealerStall::class, 'did', 'did')
            ->where('deleted', false)
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
        return 'did';
    }
}
