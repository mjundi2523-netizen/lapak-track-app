<?php

namespace App\Livewire\ExpenseCategories;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class CreateExpenseCategory extends Component
{
    use ReturnsBack;
    use Toast;

    #[Validate('required|string|max:255|unique:expense_categories,name')]
    public string $name = '';

    public function save(): void
    {
        $this->validate();

        ExpenseCategory::create([
            'name' => $this->name,
            'created_by' => Auth::id(),
        ]);

        $this->success('Kategori pengeluaran berhasil ditambahkan.');
        $this->redirectBack('expense-categories.index');
    }

    public function render()
    {
        return view('livewire.expense-categories.create');
    }
}
