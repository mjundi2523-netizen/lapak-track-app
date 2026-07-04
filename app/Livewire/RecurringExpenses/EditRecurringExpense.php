<?php

namespace App\Livewire\RecurringExpenses;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\ExpenseCategory;
use App\Models\RecurringExpense;
use App\Services\ExpenseGenerationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class EditRecurringExpense extends Component
{
    use ReturnsBack;
    use Toast;

    public RecurringExpense $recurringExpense;

    public string $title = '';
    public ?int $ecid = null;
    public int $amount = 0;
    public string $frequency = 'monthly';
    public int $interval_count = 1;
    public string $payment_method = 'tunai';
    public string $start_date = '';
    public bool $auto_post = true;
    public bool $is_active = true;
    public ?string $note = null;

    public function mount(RecurringExpense $recurringExpense): void
    {
        $this->recurringExpense = $recurringExpense;
        $this->title = $recurringExpense->title;
        $this->ecid = $recurringExpense->ecid;
        $this->amount = (int) $recurringExpense->amount;
        $this->frequency = $recurringExpense->frequency;
        $this->interval_count = (int) $recurringExpense->interval_count;
        $this->payment_method = $recurringExpense->payment_method;
        $this->start_date = $recurringExpense->start_date->toDateString();
        $this->auto_post = (bool) $recurringExpense->auto_post;
        $this->is_active = (bool) $recurringExpense->is_active;
        $this->note = $recurringExpense->note;
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'ecid' => 'required|integer|exists:expense_categories,ecid',
            'amount' => 'required|integer|min:1',
            'frequency' => 'required|in:daily,weekly,monthly,annual',
            'interval_count' => 'required|integer|min:1|max:365',
            'payment_method' => 'required|in:tunai,transfer,lainnya',
            'start_date' => 'required|date',
            'auto_post' => 'boolean',
            'is_active' => 'boolean',
            'note' => 'nullable|string|max:1000',
        ];
    }

    public function save(ExpenseGenerationService $gen): void
    {
        $this->validate();

        $this->recurringExpense->update([
            'ecid' => $this->ecid,
            'title' => $this->title,
            'amount' => $this->amount,
            'frequency' => $this->frequency,
            'interval_count' => $this->interval_count,
            'payment_method' => $this->payment_method,
            'start_date' => $this->start_date,
            'auto_post' => $this->auto_post,
            'is_active' => $this->is_active,
            'note' => $this->note,
            'modified_by' => Auth::id(),
        ]);

        // Perubahan hanya memengaruhi occurrence berikutnya (cursor generated_until tak direset).
        $gen->ensureForTemplate($this->recurringExpense->refresh());

        $this->success('Pengeluaran rutin berhasil diperbarui.');
        $this->redirectBack('recurring-expenses.index');
    }

    public function render()
    {
        return view('livewire.recurring-expenses.edit', [
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }
}
