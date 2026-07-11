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
class CreateIncomeCategory extends Component
{
    use ReturnsBack;
    use Toast;

    public string $name = '';

    public function save(): void
    {
        // Nama kategori unik PER-MARKET (bukan global).
        $this->validate([
            'name' => ['required', 'string', 'max:255',
                Rule::unique('income_categories', 'name')->where('market_id', Auth::user()->market_id)],
        ]);

        IncomeCategory::create([
            'name' => $this->name,
            'created_by' => Auth::id(),
        ]);

        $this->success('Kategori pemasukan berhasil ditambahkan.');
        $this->redirectBack('income-categories.index');
    }

    public function render()
    {
        return view('livewire.income-categories.create');
    }
}
