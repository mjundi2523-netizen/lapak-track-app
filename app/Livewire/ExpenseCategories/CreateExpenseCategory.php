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
class CreateExpenseCategory extends Component
{
    use ReturnsBack;
    use Toast;

    public string $name = '';

    public function save(): void
    {
        // Nama kategori unik PER-MARKET (bukan global).
        $this->validate([
            'name' => ['required', 'string', 'max:255',
                Rule::unique('expense_categories', 'name')->where('market_id', Auth::user()->market_id)],
        ]);

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
