<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class CreateExpense extends Component
{
    use Toast;

    public string $title = '';
    public ?int $ecid = null;
    public int $amount = 0;
    public string $expense_date = '';
    public string $payment_method = 'tunai';
    public ?string $note = null;

    public function mount(): void
    {
        $this->expense_date = Carbon::today()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'ecid' => 'required|integer|exists:expense_categories,ecid',
            'amount' => 'required|integer|min:1',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:tunai,transfer,lainnya',
            'note' => 'nullable|string|max:1000',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Expense::create([
            'ecid' => $this->ecid,
            'title' => $this->title,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
            'payment_method' => $this->payment_method,
            'note' => $this->note,
            'is_voided' => false,
            'created_by' => Auth::id(),
        ]);

        $this->success('Pengeluaran berhasil dicatat.');
        $this->redirect(route('expenses.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.expenses.create', [
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }
}
