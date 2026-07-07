<?php

namespace App\Livewire\ExpenseCategories;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class EditExpenseCategory extends Component
{
    use ReturnsBack;
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
            'name' => ['required', 'string', 'max:255',
                Rule::unique('expense_categories', 'name')
                    ->where('market_id', Auth::user()->market_id)
                    ->ignore($this->expenseCategory->ecid, 'ecid')],
        ]);

        $this->expenseCategory->update([
            'name' => $this->name,
            'modified_by' => Auth::id(),
        ]);

        $this->success('Kategori pengeluaran berhasil diperbarui.');
        $this->redirectBack('expense-categories.index');
    }

    public function render()
    {
        return view('livewire.expense-categories.edit');
    }
}
