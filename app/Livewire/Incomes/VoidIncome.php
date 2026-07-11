<?php

namespace App\Livewire\Incomes;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\Income;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class VoidIncome extends Component
{
    use ReturnsBack;
    use Toast;

    public Income $income;

    #[Validate('required|string|max:1000')]
    public string $voided_reason = '';

    public function mount(Income $income): void
    {
        abort_if($income->is_voided, 404);
        $this->income = $income->load('category');
    }

    public function void(): void
    {
        $this->validate();

        $this->income->update([
            'is_voided' => true,
            'voided_reason' => $this->voided_reason,
            'voided_at' => Carbon::now(),
            'voided_by' => Auth::id(),
            'modified_by' => Auth::id(),
        ]);

        $this->success('Pemasukan berhasil dibatalkan.');
        $this->redirectBack('incomes.index');
    }

    public function cancel(): void
    {
        $this->redirectBack('incomes.index');
    }

    public function render()
    {
        return view('livewire.incomes.void');
    }
}
