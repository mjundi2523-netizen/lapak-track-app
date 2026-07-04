<?php

namespace App\Livewire\RecurringExpenses;

use App\Models\ExpenseCategory;
use App\Models\RecurringExpense;
use App\Services\ExpenseGenerationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class CreateRecurringExpense extends Component
{
    use Toast;

    public string $title = '';
    public ?int $ecid = null;
    public int $amount = 0;
    public string $frequency = 'monthly';
    public int $interval_count = 1;
    public string $payment_method = 'tunai';
    public string $start_date = '';
    public bool $auto_post = true;
    public ?string $note = null;

    public function mount(): void
    {
        $this->start_date = Carbon::today()->toDateString();
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
            'note' => 'nullable|string|max:1000',
        ];
    }

    public function save(ExpenseGenerationService $gen): void
    {
        $this->validate();

        $template = RecurringExpense::create([
            'ecid' => $this->ecid,
            'title' => $this->title,
            'amount' => $this->amount,
            'frequency' => $this->frequency,
            'interval_count' => $this->interval_count,
            'payment_method' => $this->payment_method,
            'start_date' => $this->start_date,
            'auto_post' => $this->auto_post,
            'is_active' => true,
            'note' => $this->note,
            'created_by' => Auth::id(),
        ]);

        // Langsung buat occurrence yang sudah lewat/hari ini (catch-up).
        $gen->ensureForTemplate($template);

        $this->success('Pengeluaran rutin berhasil dibuat.');
        $this->redirect(route('recurring-expenses.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.recurring-expenses.create', [
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }
}
