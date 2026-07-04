<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseGenerationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexExpenses extends Component
{
    use Toast;
    use WithPagination;

    // Filter di query string agar dipulihkan saat kembali dari form (ReturnsBack).
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $categoryFilter = '';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    #[Url(except: 'active')]
    public string $voidedFilter = 'active';

    public function mount(ExpenseGenerationService $gen): void
    {
        // Lazy roll-forward pengeluaran rutin agar occurrence periode berjalan muncul di daftar.
        $gen->ensureAllActive();
    }

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

        // Total: hanya yang TIDAK di-void DAN sudah posted (pending = draft belum dihitung).
        $total = (int) $this->baseQuery()
            ->where('is_voided', false)
            ->where('status', 'posted')
            ->sum('amount');

        return view('livewire.expenses.index', [
            'expenses' => $expenses,
            'total' => $total,
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }
}
