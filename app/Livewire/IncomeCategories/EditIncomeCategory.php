<?php

namespace App\Livewire\IncomeCategories;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\IncomeCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class EditIncomeCategory extends Component
{
    use ReturnsBack;
    use Toast;

    public IncomeCategory $incomeCategory;

    public string $name = '';

    public function mount(IncomeCategory $incomeCategory): void
    {
        $this->incomeCategory = $incomeCategory;
        $this->name = $incomeCategory->name;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255',
                Rule::unique('income_categories', 'name')
                    ->where('market_id', Auth::user()->market_id)
                    ->ignore($this->incomeCategory->icid, 'icid')],
        ]);

        $this->incomeCategory->update([
            'name' => $this->name,
            'modified_by' => Auth::id(),
        ]);

        $this->success('Kategori pemasukan berhasil diperbarui.');
        $this->redirectBack('income-categories.index');
    }

    public function render()
    {
        return view('livewire.income-categories.edit');
    }
}
