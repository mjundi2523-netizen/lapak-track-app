<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class VoidExpense extends Component
{
    use Toast;

    public Expense $expense;

    #[Validate('required|string|max:1000')]
    public string $voided_reason = '';

    public function mount(Expense $expense): void
    {
        abort_if($expense->is_voided, 404);
        $this->expense = $expense->load('category');
    }

    public function void(): void
    {
        $this->validate();

        $this->expense->update([
            'is_voided' => true,
            'voided_reason' => $this->voided_reason,
            'voided_at' => Carbon::now(),
            'voided_by' => Auth::id(),
            'modified_by' => Auth::id(),
        ]);

        $this->success('Pengeluaran berhasil dibatalkan.');
        $this->redirect(route('expenses.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('expenses.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.expenses.void');
    }
}
