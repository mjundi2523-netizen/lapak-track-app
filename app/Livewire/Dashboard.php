<?php

namespace App\Livewire;

use App\Models\Dealer;
use App\Models\DealerBill;
use App\Models\DealerPayment;
use App\Models\Expense;
use App\Models\Stall;
use App\Services\BillGenerationService;
use App\Services\ExpenseGenerationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    use WithPagination;

    public function mount(BillGenerationService $bills, ExpenseGenerationService $expenses): void
    {
        // Lazy roll-forward: pastikan tagihan periode berjalan sudah ada saat dashboard dibuka.
        $bills->ensureAllActive();
        // Sama untuk pengeluaran rutin (auto-post langsung masuk hitungan).
        $expenses->ensureAllActive();
    }

    public function render()
    {
        $stallActive = Stall::where('is_active', true)->count();
        $stallOccupied = Stall::where('is_active', true)
            ->whereHas('activeRentals')
            ->count();

        $billCounts = DealerBill::selectRaw('billing_status, COUNT(*) as total')
            ->groupBy('billing_status')
            ->pluck('total', 'billing_status');

        // --- Kartu hero: ringkasan tagihan bulan berjalan ---
        $monthStart = Carbon::today()->startOfMonth();
        $monthEnd = Carbon::today()->endOfMonth();

        $heroTotal = (float) DealerBill::whereBetween('due_date', [$monthStart, $monthEnd])
            ->where('billing_status', '!=', 'cancelled')
            ->sum('total_amount');
        $heroPaid = (float) DealerPayment::where('is_voided', false)
            ->whereHas('dealerBill', fn ($q) => $q
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->where('billing_status', '!=', 'cancelled'))
            ->sum('paid_amount');
        $heroUnpaid = max($heroTotal - $heroPaid, 0);

        $totalPemasukan = (float) DealerPayment::where('is_voided', false)->sum('paid_amount');

        // Kas masuk bulan ini (basis payment_date) — sama dengan "Pemasukan" di Arus Kas.
        // Beda dari $heroPaid (yang berbasis due_date tagihan bulan ini) supaya Laba Bersih
        // konsisten dgn laporan Arus Kas & mencerminkan uang yang benar-benar diterima.
        $heroCashIn = (float) DealerPayment::where('is_voided', false)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->sum('paid_amount');

        // Pengeluaran bulan ini & laba bersih (kas masuk - pengeluaran).
        $heroExpense = (float) Expense::where('is_voided', false)
            ->where('status', 'posted')
            ->whereBetween('expense_date', [$monthStart, $monthEnd])
            ->sum('amount');
        $heroNet = $heroCashIn - $heroExpense;

        // --- Jatuh tempo & terlambat (paginated, badge = count asli) ---
        $overdueQuery = DealerBill::with(['dealerStall.dealer', 'dealerStall.stall', 'externalDealer.dealer'])
            ->whereDate('due_date', '<=', Carbon::today())
            ->whereNotIn('billing_status', ['paid', 'cancelled'])
            ->orderBy('due_date');

        $overdueTotal = $overdueQuery->count();
        $overdue = $overdueQuery->paginate(8);

        // --- Pembayaran terbaru (mengganti "Notifikasi" mock di desain) ---
        $recentPayments = DealerPayment::with(['dealerBill.dealerStall.dealer', 'dealerBill.externalDealer.dealer'])
            ->where('is_voided', false)
            ->orderByDesc('payment_date')
            ->orderByDesc('dpid')
            ->limit(5)
            ->get();

        // --- Pedagang menunggak & top 10 tunggakan terbesar ---
        // Outstanding = total_amount - paid, untuk bill unpaid/installment.
        $paidSub = DB::raw('(SELECT dbid, SUM(paid_amount) as paid FROM dealer_payment WHERE is_voided = 0 GROUP BY dbid) pp');

        $stallDebt = DB::table('dealer_bills')
            ->join('dealer_stall', 'dealer_bills.dsid', '=', 'dealer_stall.dsid')
            ->leftJoin($paidSub, 'pp.dbid', '=', 'dealer_bills.dbid')
            ->whereIn('dealer_bills.billing_status', ['unpaid', 'installment'])
            ->selectRaw('dealer_stall.did, SUM(dealer_bills.total_amount - COALESCE(pp.paid, 0)) as outstanding')
            ->groupBy('dealer_stall.did')
            ->get()->keyBy('did');

        $extDebt = DB::table('dealer_bills')
            ->join('external_dealers', 'dealer_bills.edid', '=', 'external_dealers.edid')
            ->leftJoin($paidSub, 'pp.dbid', '=', 'dealer_bills.dbid')
            ->whereIn('dealer_bills.billing_status', ['unpaid', 'installment'])
            ->selectRaw('external_dealers.did, SUM(dealer_bills.total_amount - COALESCE(pp.paid, 0)) as outstanding')
            ->groupBy('external_dealers.did')
            ->get()->keyBy('did');

        $debtMap = collect($stallDebt->keys())->merge($extDebt->keys())->unique()
            ->mapWithKeys(fn ($did) => [$did => (float)($stallDebt[$did]->outstanding ?? 0) + (float)($extDebt[$did]->outstanding ?? 0)])
            ->filter(fn ($v) => $v > 0)
            ->sortByDesc(fn ($v) => $v);

        $dealersWithDebt = $debtMap->count();
        $top10Dids       = $debtMap->take(10)->keys();
        $dealerNames     = Dealer::whereIn('did', $top10Dids)->pluck('name', 'did');
        $top10Debtors    = $debtMap->take(10)->map(fn ($outstanding, $did) => [
            'did'         => $did,
            'name'        => $dealerNames[$did] ?? '-',
            'outstanding' => $outstanding,
        ])->values();

        return view('livewire.dashboard', [
            'stallTotal' => Stall::count(),
            'stallActive' => $stallActive,
            'stallOccupied' => $stallOccupied,
            'stallEmpty' => max($stallActive - $stallOccupied, 0),
            'dealerActive' => Dealer::where('status', 'active')->count(),
            'dealerInactive' => Dealer::where('status', 'inactive')->count(),
            'billPaid' => $billCounts['paid'] ?? 0,
            'billInstallment' => $billCounts['installment'] ?? 0,
            'billUnpaid' => $billCounts['unpaid'] ?? 0,
            'billPending' => $billCounts['pending'] ?? 0,
            'heroTotal' => $heroTotal,
            'heroPaid' => $heroPaid,
            'heroUnpaid' => $heroUnpaid,
            'heroExpense' => $heroExpense,
            'heroNet' => $heroNet,
            'totalPemasukan' => $totalPemasukan,
            'overdue'          => $overdue,
            'overdueTotal'     => $overdueTotal,
            'recentPayments'   => $recentPayments,
            'dealersWithDebt'  => $dealersWithDebt,
            'top10Debtors'     => $top10Debtors,
            'chart'            => $this->buildChart(),
        ]);
    }

    /**
     * Seri bulanan 8 bulan terakhir: Tagihan (sum total_amount per due_date)
     * vs Terbayar (sum paid_amount non-void per payment_date), siap dirender SVG.
     */
    protected function buildChart(): array
    {
        $count = 8;
        $first = Carbon::today()->startOfMonth()->subMonths($count - 1);
        $rangeEnd = Carbon::today()->endOfMonth();

        $billed = DealerBill::selectRaw("DATE_FORMAT(due_date, '%Y-%m') ym, SUM(total_amount) t")
            ->whereBetween('due_date', [$first, $rangeEnd])
            ->where('billing_status', '!=', 'cancelled')
            ->groupBy('ym')->pluck('t', 'ym');

        $paid = DealerPayment::where('is_voided', false)
            ->whereHas('dealerBill', fn ($q) => $q->where('billing_status', '!=', 'cancelled'))
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') ym, SUM(paid_amount) t")
            ->whereBetween('payment_date', [$first, $rangeEnd])
            ->groupBy('ym')->pluck('t', 'ym');

        $spent = Expense::where('is_voided', false)
            ->where('status', 'posted')
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') ym, SUM(amount) t")
            ->whereBetween('expense_date', [$first, $rangeEnd])
            ->groupBy('ym')->pluck('t', 'ym');

        $a = $b = $c = $labels = [];
        for ($i = 0; $i < $count; $i++) {
            $m = $first->copy()->addMonths($i);
            $ym = $m->format('Y-m');
            $a[] = (float) ($billed[$ym] ?? 0);
            $b[] = (float) ($paid[$ym] ?? 0);
            $c[] = (float) ($spent[$ym] ?? 0);
            $labels[] = $m->format('M');
        }

        // Geometri plot (viewBox 720x300).
        $padL = 44; $padR = 8; $padTop = 18; $padBottom = 34;
        $w = 720; $h = 300;
        $plotW = $w - $padL - $padR;
        $plotH = $h - $padTop - $padBottom;
        $n = $count;
        $max = max(max($a), max($b), max($c), 1);
        $max *= 1.12; // headroom

        $x = fn ($i) => $padL + ($n > 1 ? $i * $plotW / ($n - 1) : 0);
        $y = fn ($v) => $padTop + $plotH * (1 - $v / $max);

        $ptsA = $ptsB = $ptsC = [];
        for ($i = 0; $i < $n; $i++) {
            $ptsA[] = [round($x($i), 1), round($y($a[$i]), 1)];
            $ptsB[] = [round($x($i), 1), round($y($b[$i]), 1)];
            $ptsC[] = [round($x($i), 1), round($y($c[$i]), 1)];
        }

        $grid = [];
        for ($g = 0; $g <= 4; $g++) {
            $val = $max * $g / 4;
            $grid[] = ['y' => round($padTop + $plotH * (1 - $g / 4), 1), 'label' => $this->axisRp($val)];
        }

        $xlabels = [];
        for ($i = 0; $i < $n; $i++) {
            $xlabels[] = ['x' => round($x($i), 1), 'label' => $labels[$i]];
        }

        $baseY = round($padTop + $plotH, 1);

        return [
            'w' => $w, 'h' => $h, 'baseY' => $baseY,
            'a' => $ptsA, 'b' => $ptsB, 'c' => $ptsC,
            'grid' => $grid, 'xlabels' => $xlabels,
        ];
    }

    protected function axisRp(float $v): string
    {
        if ($v >= 1_000_000) {
            return rtrim(rtrim(number_format($v / 1_000_000, 1, ',', '.'), '0'), ',').'jt';
        }
        if ($v >= 1_000) {
            return round($v / 1_000).'rb';
        }
        return (string) round($v);
    }
}
