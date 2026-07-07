<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RecurringExpense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Generator pengeluaran rutin (lazy roll-forward), mengikuti pola BillGenerationService.
 *
 * Kunci idempotensi: cursor `recurring_expenses.generated_until` = tanggal occurrence
 * terakhir yang sudah dibuat. Sengaja DEKOPEL dari baris `expenses` supaya aksi user
 * "Tunda" (ubah expense_date) atau "Batalkan" (hapus occurrence) tidak memicu regenerasi.
 *
 * Mode per-template (`auto_post`):
 *  - true  → occurrence dibuat langsung `posted` (masuk hitungan) untuk biaya tetap.
 *  - false → occurrence dibuat `pending` (draft) untuk dikonfirmasi nominal aktualnya.
 */
class ExpenseGenerationService
{
    /** Pengaman agar tidak pernah membuat occurrence tak terhingga dalam 1 panggilan. */
    protected const MAX_PERIODS_PER_RUN = 1000;

    /** Jalankan catch-up untuk semua template aktif; kembalikan jumlah occurrence baru. */
    public function ensureAllActive(): int
    {
        $before = Expense::count();

        RecurringExpense::where('is_active', true)
            ->get()
            ->each(fn (RecurringExpense $r) => $this->ensureForTemplate($r));

        return Expense::count() - $before;
    }

    /** Pastikan occurrence satu template sudah dibuat sampai hari ini. */
    public function ensureForTemplate(RecurringExpense $r): void
    {
        if (! $r->is_active) {
            return;
        }

        $start = Carbon::parse($r->start_date)->startOfDay();
        // Horizon = hari ini, tapi tidak melewati end_date bila diisi (template berbatas waktu).
        $horizon = $r->end_date
            ? Carbon::parse($r->end_date)->startOfDay()->min(Carbon::today())
            : Carbon::today();

        if ($start->gt($horizon)) {
            return;
        }

        $interval = max(1, (int) ($r->interval_count ?? 1));
        $userId = Auth::id() ?? $r->created_by ?? 1;

        DB::transaction(function () use ($r, $start, $horizon, $interval, $userId) {
            $next = $r->generated_until
                ? $this->advance(Carbon::parse($r->generated_until)->startOfDay(), $r->frequency, $interval)
                : $start->copy();

            $guard = 0;
            while ($next->lte($horizon) && $guard < self::MAX_PERIODS_PER_RUN) {
                $guard++;

                Expense::create([
                    'market_id' => $r->market_id,
                    'ecid' => $r->ecid,
                    'rxid' => $r->rxid,
                    'title' => $r->title,
                    'amount' => (int) $r->amount,
                    'expense_date' => $next->copy(),
                    'payment_method' => $r->payment_method ?? 'tunai',
                    'note' => $r->note,
                    'status' => $r->auto_post ? 'posted' : 'pending',
                    'is_voided' => false,
                    'created_by' => $userId,
                ]);

                $r->generated_until = $next->copy();
                $next = $this->advance($next->copy(), $r->frequency, $interval);
            }

            $r->save();
        });
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
