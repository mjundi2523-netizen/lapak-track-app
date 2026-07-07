<?php

namespace App\Services;

use App\Models\DealerBill;
use App\Models\DealerStall;
use App\Models\ExternalDealer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillGenerationService
{
    /** Pengaman agar tidak pernah membuat tagihan tak terhingga dalam 1 panggilan per stream. */
    protected const MAX_PERIODS_PER_RUN = 1000;

    public function __construct(protected BillIdGenerator $billIdGenerator) {}

    /**
     * Pastikan semua tagihan untuk satu rental sudah dibuat sampai hari ini (lazy roll-forward).
     * Idempoten: cursor tiap stream = max(period_end) tagihan yang sudah ada.
     *
     * Tipe tagihan:
     *  - MTR: sewa saja (rent-anchored, frekuensi = payment_term, tanpa add-on sefrekuensi)
     *  - MAT: sewa + add-on(is_rent_date=true) frekuensi sama (digabung 1 baris)
     *  - AAT: add-on(is_rent_date=true) saja, frekuensi != sewa (digabung per frekuensi)
     *  - ATR: add-on(is_rent_date=false), per-add-on, anchor = start_date sendiri
     *
     * Stream rent-anchored di-key per FREKUENSI (selalu 1 stream/frekuensi, anti-collision cursor).
     * is_rent_date=true berarti add-on mengikuti jadwal sewa sepenuhnya (anchor + periode).
     */
    public function ensureBillsUpToDate(DealerStall $ds): void
    {
        if ($ds->deleted) {
            return;
        }

        $ds->loadMissing(['stall.paymentTerm', 'stall.addOns']);
        $stall = $ds->stall;

        if (! $stall) {
            return;
        }

        $rentStart = Carbon::parse($ds->rent_start_date)->startOfDay();
        $today = Carbon::today();
        $horizon = $ds->rent_end_date
            ? Carbon::parse($ds->rent_end_date)->startOfDay()->min($today)
            : $today;

        if ($rentStart->gt($horizon)) {
            return;
        }

        $userId = Auth::id() ?? $ds->created_by ?? 1;

        DB::transaction(function () use ($ds, $stall, $rentStart, $horizon, $userId) {
            $term = $stall->paymentTerm;
            $rentFreq = $term && $term->price > 0 ? $term->frequency : null;
            $rentInterval = $term ? max(1, (int) ($term->interval_count ?? 1)) : 1;

            // --- Stream rent-anchored, dikelompokkan per frekuensi ---
            // streams[freq] = ['rent' => int, 'addon' => int]
            $streams = [];

            if ($rentFreq) {
                $streams[$rentFreq] = ['rent' => (int) $term->price, 'addon' => 0, 'addon_count' => 0];
            }

            foreach ($stall->addOns as $addOn) {
                if ((int) $addOn->price <= 0 || ! $addOn->is_rent_date) {
                    continue;
                }
                $f = $addOn->frequency;
                $streams[$f] ??= ['rent' => 0, 'addon' => 0, 'addon_count' => 0];
                $streams[$f]['addon'] += (int) $addOn->price;
                $streams[$f]['addon_count']++;
            }

            foreach ($streams as $frequency => $parts) {
                $amount = $parts['rent'] + $parts['addon'];
                if ($amount <= 0) {
                    continue;
                }

                $type = match (true) {
                    $parts['rent'] > 0 && $parts['addon'] > 0 => 'MAT',   // sewa + add-on(s)
                    $parts['rent'] > 0                         => 'MTR',   // sewa saja
                    ($parts['addon_count'] ?? 0) > 1          => 'AAT',   // add-on + add-on
                    default                                    => 'ATR',   // 1 add-on saja
                };
                // Periode rent memakai interval_count payment_term; stream add-on murni = 1.
                $interval = $parts['rent'] > 0 ? $rentInterval : 1;

                $this->generateStream(
                    $ds, $type, $frequency, $interval, $amount,
                    $rentStart, $rentStart, $horizon, $userId, null
                );
            }

            // --- Stream ATR: add-on jadwal-sendiri (is_rent_date=false), per add-on ---
            foreach ($stall->addOns as $addOn) {
                if ((int) $addOn->price <= 0 || $addOn->is_rent_date) {
                    continue;
                }

                $anchor = $addOn->start_date
                    ? Carbon::parse($addOn->start_date)->startOfDay()
                    : $rentStart->copy();

                $this->generateStream(
                    $ds, 'ATR', $addOn->frequency, 1, (int) $addOn->price,
                    $anchor, $rentStart, $horizon, $userId, $addOn->aoid
                );
            }
        });
    }

    /**
     * Jalankan catch-up untuk semua rental aktif (dipakai lazy saat halaman dibuka / via command).
     */
    public function ensureAllActive(): int
    {
        $before = DealerBill::count();

        DealerStall::where('deleted', false)
            ->with(['stall.paymentTerm', 'stall.addOns'])
            ->get()
            ->each(fn (DealerStall $ds) => $this->ensureBillsUpToDate($ds));

        // Langganan pedagang eksternal (relasi langsung ke payment_terms).
        ExternalDealer::where('deleted', false)
            ->with('paymentTerm')
            ->get()
            ->each(fn (ExternalDealer $ed) => $this->ensureExternalBillsUpToDate($ed));

        // Refresh status tersimpan: tagihan 'pending' yang sudah lewat jatuh tempo jadi 'unpaid'.
        DealerBill::where('billing_status', 'pending')
            ->whereDate('due_date', '<=', Carbon::today())
            ->update(['billing_status' => 'unpaid']);

        return DealerBill::count() - $before;
    }

    /**
     * Lazy roll-forward untuk langganan pedagang eksternal (tanpa lapak).
     * Anchor = external_dealers.start_date, term dari external_dealers.ptid, key cursor (edid, frequency).
     */
    public function ensureExternalBillsUpToDate(ExternalDealer $ed): void
    {
        if ($ed->deleted) {
            return;
        }

        $ed->loadMissing('paymentTerm');
        $term = $ed->paymentTerm;

        if (! $term || $term->price <= 0) {
            return;
        }

        $start = Carbon::parse($ed->start_date)->startOfDay();
        $today = Carbon::today();
        $horizon = $ed->end_date
            ? Carbon::parse($ed->end_date)->startOfDay()->min($today)
            : $today;

        if ($start->gt($horizon)) {
            return;
        }

        $userId = Auth::id() ?? $ed->created_by ?? 1;
        $interval = max(1, (int) ($term->interval_count ?? 1));
        $amount = (int) $term->price;
        $frequency = $term->frequency;

        DB::transaction(function () use ($ed, $frequency, $interval, $amount, $start, $horizon, $userId) {
            $lastEnd = DealerBill::where('edid', $ed->edid)
                ->where('frequency', $frequency)
                ->max('period_end');

            $periodStart = $lastEnd ? Carbon::parse($lastEnd)->startOfDay() : $start->copy();
            $guard = 0;

            while ($periodStart->lte($horizon) && $guard < self::MAX_PERIODS_PER_RUN) {
                $guard++;

                $periodEnd = $this->advance($periodStart->copy(), $frequency, $interval);
                $dueDate = $periodEnd->copy();

                DealerBill::create([
                    'market_id' => $ed->market_id,
                    'bill_id' => $this->billIdGenerator->generate('dealer_bills', 'EXT', Carbon::now()),
                    'bill_type' => 'EXT',
                    'frequency' => $frequency,
                    'aoid' => null,
                    'dsid' => null,
                    'edid' => $ed->edid,
                    'total_amount' => $amount,
                    'due_date' => $dueDate,
                    'billing_status' => DealerBill::deriveStatus(0, $amount, $dueDate),
                    'period_start' => $periodStart->copy(),
                    'period_end' => $periodEnd->copy(),
                    'created_by' => $userId,
                ]);

                $periodStart = $periodEnd->copy();
            }
        });
    }

    /**
     * Generate tagihan satu stream dari cursor (period_end terakhir) sampai periode berjalan.
     *
     * Kunci cursor:
     *  - ATR (aoid != null): (dsid, aoid)
     *  - rent-anchored (aoid null): (dsid, frequency, aoid IS NULL) — 1 stream/frekuensi
     *
     * $anchor = fase awal siklus (rent_start untuk rent-anchored, start_date untuk ATR).
     * $rentStart = batas bawah; periode yang berakhir sebelum/di rentStart di-skip.
     */
    protected function generateStream(
        DealerStall $ds,
        string $type,
        string $frequency,
        int $interval,
        int $amount,
        Carbon $anchor,
        Carbon $rentStart,
        Carbon $horizon,
        int $userId,
        ?int $aoid
    ): void {
        $cursor = DealerBill::where('dsid', $ds->dsid)
            ->when($aoid !== null,
                fn ($q) => $q->where('aoid', $aoid),
                fn ($q) => $q->whereNull('aoid')->where('frequency', $frequency)
            );

        $lastEnd = $cursor->max('period_end');

        $periodStart = $lastEnd ? Carbon::parse($lastEnd)->startOfDay() : $anchor->copy();

        $guard = 0;

        while ($periodStart->lte($horizon) && $guard < self::MAX_PERIODS_PER_RUN) {
            $guard++;

            $periodEnd = $this->advance($periodStart->copy(), $frequency, $interval);

            // Skip periode yang seluruhnya sebelum sewa mulai (anchor bisa < rent_start untuk ATR).
            if ($periodEnd->lte($rentStart)) {
                $periodStart = $periodEnd->copy();
                continue;
            }

            $dueDate = $periodEnd->copy();
            $status = DealerBill::deriveStatus(0, $amount, $dueDate);

            DealerBill::create([
                'market_id' => $ds->market_id,
                'bill_id' => $this->billIdGenerator->generate('dealer_bills', $type, Carbon::now()),
                'bill_type' => $type,
                'frequency' => $frequency,
                'aoid' => $aoid,
                'dsid' => $ds->dsid,
                'total_amount' => $amount,
                'due_date' => $dueDate,
                'billing_status' => $status,
                'period_start' => $periodStart->copy(),
                'period_end' => $periodEnd->copy(),
                'created_by' => $userId,
            ]);

            $periodStart = $periodEnd->copy();
        }
    }

    protected function advance(Carbon $date, string $frequency, int $interval): Carbon
    {
        return match ($frequency) {
            'daily' => $date->addDays($interval),
            'weekly' => $date->addWeeks($interval),
            'monthly' => $date->addMonths($interval),
            'annual' => $date->addYears($interval),
            default => $date->addMonths($interval),
        };
    }
}
