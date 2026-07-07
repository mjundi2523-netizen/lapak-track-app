<?php

namespace App\Models;

use App\Models\Concerns\BelongsToMarket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class DealerBill extends Model
{
    use BelongsToMarket;

    protected $table = 'dealer_bills';
    protected $primaryKey = 'dbid';

    protected $fillable = [
        'market_id',
        'bill_id',
        'bill_type',
        'frequency',
        'aoid',
        'dsid',
        'edid',
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

    public function externalDealer(): BelongsTo
    {
        return $this->belongsTo(ExternalDealer::class, 'edid', 'edid');
    }

    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class, 'aoid', 'aoid');
    }

    /**
     * Pemilik tagihan, dari salah satu parent: dealer_stall (sewa) atau external_dealers.
     * Pastikan relasi terkait sudah di-eager-load untuk hindari N+1.
     */
    public function getHolderAttribute(): ?Dealer
    {
        return $this->dealerStall?->dealer ?? $this->externalDealer?->dealer;
    }

    /** Label lokasi: blok lapak (sewa) atau "Eksternal". */
    public function getLocationLabelAttribute(): string
    {
        return $this->dealerStall?->stall?->code ?? ($this->edid ? 'Eksternal' : '-');
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
        // Rincian ditentukan oleh stream `(aoid, frequency)` — sama seperti
        // pengelompokan BillGenerationService — BUKAN oleh label `bill_type`
        // (label hanya turunan dari jumlah komponen: MTR/ATR/MAT/AAT).

        // Tagihan eksternal: komponennya = aturan bayar langganan.
        if ($this->edid !== null) {
            $term = $this->externalDealer?->paymentTerm;

            return $term ? [['label' => $term->term_name, 'amount' => (int) $term->price]] : [];
        }

        // Stream jadwal-sendiri (aoid terisi): satu add-on spesifik.
        if ($this->aoid !== null) {
            $addOn = $this->addOn;

            return $addOn
                ? [['label' => $addOn->add_on, 'amount' => (int) $addOn->price]]
                : [];
        }

        // Stream rent-anchored (aoid NULL): gabungan sewa + add-on
        // (`is_rent_date=true`) yang frekuensinya sama dengan tagihan ini.
        $stall = $this->dealerStall?->stall;
        if (! $stall) {
            return [];
        }

        $items = [];

        $term = $stall->paymentTerm;
        if ($term && (int) $term->price > 0 && $term->frequency === $this->frequency) {
            $items[] = ['label' => 'Sewa Lapak', 'amount' => (int) $term->price];
        }

        foreach ($stall->addOns as $addOn) {
            if ($addOn->is_rent_date && (int) $addOn->price > 0 && $addOn->frequency === $this->frequency) {
                $items[] = ['label' => $addOn->add_on, 'amount' => (int) $addOn->price];
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
        // Tagihan yang dibatalkan adalah status terminal — jangan dihidupkan kembali.
        if ($this->billing_status === 'cancelled') {
            return;
        }

        $this->billing_status = self::deriveStatus($this->paidAmount(), (float) $this->total_amount, $this->due_date);
        $this->save();
    }
}
