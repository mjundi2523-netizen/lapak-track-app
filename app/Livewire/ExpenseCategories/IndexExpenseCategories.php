<?php

namespace App\Livewire\ExpenseCategories;

use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexExpenseCategories extends Component
{
    use Toast;
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public function delete(ExpenseCategory $expenseCategory): void
    {
        if ($expenseCategory->expenses()->exists()) {
            $this->error('Kategori tidak bisa dihapus karena masih dipakai pengeluaran.');

            return;
        }

        $expenseCategory->delete();
        $this->success('Kategori pengeluaran berhasil dihapus.');
    }

    public function render()
    {
        $categories = ExpenseCategory::query()
            ->withCount('expenses')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.expense-categories.index', [
            'categories' => $categories,
        ]);
    }
}
