<?php

namespace App\Livewire\IncomeCategories;

use App\Livewire\Concerns\Sortable;
use App\Models\IncomeCategory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexIncomeCategories extends Component
{
    use Sortable;
    use Toast;
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public function delete(IncomeCategory $incomeCategory): void
    {
        if ($incomeCategory->incomes()->exists()) {
            $this->error('Kategori tidak bisa dihapus karena masih dipakai pemasukan.');

            return;
        }

        $incomeCategory->delete();
        $this->success('Kategori pemasukan berhasil dihapus.');
    }

    /** Kolom sortable (klik header). */
    protected function sortColumns(): array
    {
        return [
            'name' => 'name',
            'incomes_count' => '(SELECT COUNT(*) FROM incomes i WHERE i.icid = income_categories.icid)',
        ];
    }

    public function render()
    {
        $categories = IncomeCategory::query()
            ->withCount('incomes')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"));

        $this->applySort($categories, fn ($q) => $q->orderBy('name'));

        $categories = $categories->paginate(10);

        return view('livewire.income-categories.index', [
            'categories' => $categories,
        ]);
    }
}
