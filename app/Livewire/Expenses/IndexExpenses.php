<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexExpenses extends Component
{
    use Toast;
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $voidedFilter = 'active';

    public function updated($prop): void
    {
        // Reset paginasi saat filter berubah.
        if (in_array($prop, ['search', 'categoryFilter', 'dateFrom', 'dateTo', 'voidedFilter'], true)) {
            $this->resetPage();
        }
    }

    protected function baseQuery()
    {
        return Expense::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn ($q) => $q->where('ecid', $this->categoryFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('expense_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('expense_date', '<=', $this->dateTo))
            ->when($this->voidedFilter === 'active', fn ($q) => $q->where('is_voided', false))
            ->when($this->voidedFilter === 'voided', fn ($q) => $q->where('is_voided', true));
    }

    public function render()
    {
        $expenses = $this->baseQuery()
            ->with('category')
            ->orderByDesc('expense_date')
            ->orderByDesc('xpid')
            ->paginate(10);

        // Total hanya dari yang TIDAK di-void (pengeluaran sah) dalam filter aktif.
        $total = (int) $this->baseQuery()->where('is_voided', false)->sum('amount');

        return view('livewire.expenses.index', [
            'expenses' => $expenses,
            'total' => $total,
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }
}
