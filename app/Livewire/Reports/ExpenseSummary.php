<?php

namespace App\Livewire\Reports;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ExpenseSummary extends Component
{
    use WithPagination;

    #[Url]
    public int $year = 0;

    #[Url(except: 0)]
    public int $month = 0; // 0 = semua bulan

    #[Url(except: '')]
    public string $categoryFilter = '';

    public function mount(): void
    {
        if (! $this->year) {
            $this->year = (int) Carbon::today()->year;
        }
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['year', 'month', 'categoryFilter'], true)) {
            $this->resetPage();
        }
    }

    /** Query dasar: pengeluaran sah (posted, non-void) sesuai filter periode/kategori. */
    protected function baseQuery()
    {
        return Expense::query()
            ->where('is_voided', false)
            ->where('status', 'posted')
            ->whereYear('expense_date', $this->year)
            ->when($this->month, fn ($q) => $q->whereMonth('expense_date', $this->month))
            ->when($this->categoryFilter, fn ($q) => $q->where('ecid', $this->categoryFilter));
    }

    public function render()
    {
        // Ringkasan keseluruhan.
        $grandTotal = (float) $this->baseQuery()->sum('amount');
        $grandCount = (int) $this->baseQuery()->count();
        $avg = $grandCount ? $grandTotal / $grandCount : 0.0;

        // Rincian per kategori (+ persentase terhadap total).
        $catNames = ExpenseCategory::pluck('name', 'ecid');
        $byCategory = $this->baseQuery()
            ->selectRaw('ecid, COUNT(*) c, SUM(amount) t')
            ->groupBy('ecid')
            ->orderByDesc('t')
            ->get()
            ->map(fn ($r) => [
                'name' => $catNames[$r->ecid] ?? '-',
                'count' => (int) $r->c,
                'total' => (float) $r->t,
                'pct' => $grandTotal > 0 ? round($r->t / $grandTotal * 100, 1) : 0,
            ]);

        // Rincian per metode pembayaran.
        $byMethod = $this->baseQuery()
            ->selectRaw('payment_method, COUNT(*) c, SUM(amount) t')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        // Rincian per bulan (mengikuti tahun; jika bulan dipilih, hanya bulan itu).
        $perMonth = $this->baseQuery()
            ->selectRaw('MONTH(expense_date) m, SUM(amount) t')
            ->groupBy('m')->pluck('t', 'm');

        $loopMonths = $this->month ? [$this->month] : range(1, 12);
        $monthlyRows = [];
        foreach ($loopMonths as $m) {
            $monthlyRows[] = [
                'month' => Carbon::create($this->year, $m, 1)->locale('id')->translatedFormat('F'),
                'total' => (float) ($perMonth[$m] ?? 0),
            ];
        }

        // Tabel detail (paginated).
        $expenses = $this->baseQuery()
            ->with('category')
            ->orderByDesc('expense_date')
            ->orderByDesc('xpid')
            ->paginate(15);

        // Opsi dropdown.
        $years = collect([$this->year])
            ->merge(Expense::selectRaw('DISTINCT YEAR(expense_date) y')->pluck('y'))
            ->filter()->map(fn ($y) => (int) $y)->unique()->sortDesc()->values();

        $months = collect([['id' => 0, 'name' => 'Semua Bulan']]);
        for ($m = 1; $m <= 12; $m++) {
            $months->push(['id' => $m, 'name' => Carbon::create($this->year, $m, 1)->locale('id')->translatedFormat('F')]);
        }

        $periodLabel = $this->month
            ? Carbon::create($this->year, $this->month, 1)->locale('id')->translatedFormat('F') . ' ' . $this->year
            : (string) $this->year;

        return view('livewire.reports.expense-summary', [
            'grandTotal' => $grandTotal,
            'grandCount' => $grandCount,
            'avg' => $avg,
            'byCategory' => $byCategory,
            'tunaiTotal' => (float) ($byMethod['tunai']->t ?? 0),
            'transferTotal' => (float) ($byMethod['transfer']->t ?? 0),
            'lainnyaTotal' => (float) ($byMethod['lainnya']->t ?? 0),
            'monthlyRows' => $monthlyRows,
            'expenses' => $expenses,
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'years' => $years,
            'months' => $months,
            'periodLabel' => $periodLabel,
        ]);
    }
}
