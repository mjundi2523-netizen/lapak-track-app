<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class DealerBill extends Model
{
    protected $table = 'dealer_bills';
    protected $primaryKey = 'dbid';

    protected $fillable = [
        'bill_id',
        'bill_type',
        'frequency',
        'aoid',
        'dsid',
        'total_amount',
        'due_date',
        'billing_status',
        'period_start',
        'period_end',
        'created_by',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'due_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function dealerStall(): BelongsTo
    {
        return $this->belongsTo(DealerStall::class, 'dsid', 'dsid');
    }

    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class, 'aoid', 'aoid');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DealerPayment::class, 'dbid', 'dbid');
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
        return 'dbid';
    }

    /**
     * Rincian komponen tagihan, direkonstruksi dari konfigurasi lapak saat ini
     * (mengikuti logika BillGenerationService). Mengembalikan array berisi
     * ['label' => string, 'amount' => int].
     *
     * Catatan: bila konfigurasi sewa/add-on berubah setelah tagihan dibuat,
     * jumlah rincian bisa berbeda dari `total_amount` tersimpan.
     */
    public function breakdown(): array
    {
        // ATR: satu add-on spesifik (jadwal sendiri).
        if ($this->bill_type === 'ATR') {
            $addOn = $this->addOn;

            return $addOn
                ? [['label' => $addOn->add_on, 'amount' => (int) $addOn->price]]
                : [];
        }

        $stall = $this->dealerStall?->stall;
        if (! $stall) {
            return [];
        }

        $items = [];

        // Komponen sewa (MTR/MAT).
        if (in_array($this->bill_type, ['MTR', 'MAT'], true)) {
            $term = $stall->paymentTerm;
            if ($term && (int) $term->price > 0) {
                $items[] = ['label' => 'Sewa Lapak', 'amount' => (int) $term->price];
            }
        }

        // Add-on ikut-tanggal-sewa, frekuensi sama (MAT/AAT).
        if (in_array($this->bill_type, ['MAT', 'AAT'], true)) {
            foreach ($stall->addOns as $addOn) {
                if ($addOn->is_rent_date && (int) $addOn->price > 0 && $addOn->frequency === $this->frequency) {
                    $items[] = ['label' => $addOn->add_on, 'amount' => (int) $addOn->price];
                }
            }
        }

        return $items;
    }

    /**
     * Total dibayar (tidak termasuk yang di-void).
     */
    public function paidAmount(): float
    {
        return (float) $this->payments()->where('is_voided', false)->sum('paid_amount');
    }

    /**
     * Sisa yang harus dibayar.
     */
    public function outstanding(): float
    {
        return max((float) $this->total_amount - $this->paidAmount(), 0);
    }

    /**
     * Status 4-state yang diturunkan murni dari pembayaran & jatuh tempo:
     *  paid        -> sudah lunas
     *  installment -> dibayar sebagian
     *  unpaid      -> belum bayar & sudah jatuh tempo
     *  pending     -> belum bayar & belum jatuh tempo
     */
    public static function deriveStatus(float $paid, float $total, $dueDate): string
    {
        if ($paid >= $total) {
            return 'paid';
        }

        if ($paid > 0) {
            return 'installment';
        }

        return Carbon::parse($dueDate)->startOfDay()->lte(Carbon::today()) ? 'unpaid' : 'pending';
    }

    /**
     * Hitung ulang & simpan billing_status dari pembayaran aktual.
     */
    public function recalculateBillingStatus(): void
    {
        $this->billing_status = self::deriveStatus($this->paidAmount(), (float) $this->total_amount, $this->due_date);
        $this->save();
    }
}
