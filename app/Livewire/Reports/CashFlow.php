<?php

namespace App\Livewire\Reports;

use App\Models\DealerPayment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CashFlow extends Component
{
    public int $year;

    public function mount(): void
    {
        $this->year = (int) Carbon::today()->year;
    }

    public function render()
    {
        $year = $this->year;

        $income = DealerPayment::where('is_voided', false)
            ->whereYear('payment_date', $year)
            ->selectRaw('MONTH(payment_date) m, SUM(paid_amount) t')
            ->groupBy('m')->pluck('t', 'm');

        $expense = Expense::where('is_voided', false)
            ->whereYear('expense_date', $year)
            ->selectRaw('MONTH(expense_date) m, SUM(amount) t')
            ->groupBy('m')->pluck('t', 'm');

        $rows = [];
        $totalIncome = $totalExpense = 0;
        for ($m = 1; $m <= 12; $m++) {
            $in = (float) ($income[$m] ?? 0);
            $out = (float) ($expense[$m] ?? 0);
            $totalIncome += $in;
            $totalExpense += $out;
            $rows[] = [
                'month' => Carbon::create($year, $m, 1)->locale('id')->translatedFormat('F'),
                'income' => $in,
                'expense' => $out,
                'net' => $in - $out,
            ];
        }

        // Rincian pengeluaran per kategori (setahun).
        $catNames = ExpenseCategory::pluck('name', 'ecid');
        $byCategory = Expense::where('is_voided', false)
            ->whereYear('expense_date', $year)
            ->selectRaw('ecid, SUM(amount) t')
            ->groupBy('ecid')
            ->orderByDesc('t')
            ->get()
            ->map(fn ($r) => ['name' => $catNames[$r->ecid] ?? '-', 'total' => (float) $r->t]);

        // Tahun yang tersedia (dari data) untuk dropdown.
        $years = collect([$year])
            ->merge(DealerPayment::selectRaw('DISTINCT YEAR(payment_date) y')->pluck('y'))
            ->merge(Expense::selectRaw('DISTINCT YEAR(expense_date) y')->pluck('y'))
            ->filter()->map(fn ($y) => (int) $y)->unique()->sortDesc()->values();

        return view('livewire.reports.cash-flow', [
            'rows' => $rows,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalNet' => $totalIncome - $totalExpense,
            'byCategory' => $byCategory,
            'years' => $years,
        ]);
    }
}
