<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Dealer extends Model
{
    protected $table = 'dealer';
    protected $primaryKey = 'did';

    protected $fillable = [
        'nik',
        'name',
        'birth_date',
        'address',
        'phone_number_1',
        'phone_number_2',
        'product_type',
        'status',
        'is_new',
        'scan_id',
        'letter_no',
        'created_by',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_new' => 'boolean',
        ];
    }

    public function dealerStalls(): HasMany
    {
        return $this->hasMany(DealerStall::class, 'did', 'did');
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
