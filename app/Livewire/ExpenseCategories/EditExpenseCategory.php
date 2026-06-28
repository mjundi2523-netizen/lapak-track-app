<?php

namespace App\Livewire\ExpenseCategories;

use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class EditExpenseCategory extends Component
{
    use Toast;

    public ExpenseCategory $expenseCategory;

    public string $name = '';

    public function mount(ExpenseCategory $expenseCategory): void
    {
        $this->expenseCategory = $expenseCategory;
        $this->name = $expenseCategory->name;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $this->expenseCategory->ecid . ',ecid',
        ]);

        $this->expenseCategory->update([
            'name' => $this->name,
            'modified_by' => Auth::id(),
        ]);

        $this->success('Kategori pengeluaran berhasil diperbarui.');
        $this->redirect(route('expense-categories.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.expense-categories.edit');
    }
}
