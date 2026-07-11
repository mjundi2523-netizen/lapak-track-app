<?php

namespace App\Livewire\Incomes;

use App\Livewire\Concerns\Sortable;
use App\Models\Income;
use App\Models\IncomeCategory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexIncomes extends Component
{
    use Sortable;
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

    public function updated($prop): void
    {
        // Reset paginasi saat filter berubah.
        if (in_array($prop, ['search', 'categoryFilter', 'dateFrom', 'dateTo', 'voidedFilter'], true)) {
            $this->resetPage();
        }
    }

    protected function baseQuery()
    {
        return Income::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn ($q) => $q->where('icid', $this->categoryFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('income_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('income_date', '<=', $this->dateTo))
            ->when($this->voidedFilter === 'active', fn ($q) => $q->where('is_voided', false))
            ->when($this->voidedFilter === 'voided', fn ($q) => $q->where('is_voided', true));
    }

    /** Kolom sortable (klik header). */
    protected function sortColumns(): array
    {
        return [
            'income_date' => 'income_date',
            'title' => 'title',
            'category' => '(SELECT ic.name FROM income_categories ic WHERE ic.icid = incomes.icid)',
            'payment_method' => 'payment_method',
            'amount' => 'amount',
            'status' => 'is_voided',
        ];
    }

    public function render()
    {
        $incomes = $this->baseQuery()->with('category');

        $this->applySort($incomes, fn ($q) => $q->orderByDesc('income_date')->orderByDesc('imid'));

        $incomes = $incomes->paginate(10);

        // Total: hanya yang TIDAK di-void.
        $total = (int) $this->baseQuery()
            ->where('is_voided', false)
            ->sum('amount');

        return view('livewire.incomes.index', [
            'incomes' => $incomes,
            'total' => $total,
            'categories' => IncomeCategory::orderBy('name')->get(),
        ]);
    }
}
