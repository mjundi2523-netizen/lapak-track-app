<?php

namespace App\Livewire\Incomes;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\Income;
use App\Models\IncomeCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class CreateIncome extends Component
{
    use ReturnsBack;
    use Toast;

    public string $title = '';
    public ?int $icid = null;
    public int $amount = 0;
    public string $income_date = '';
    public string $payment_method = 'tunai';
    public ?string $note = null;

    public function mount(): void
    {
        $this->income_date = Carbon::today()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'icid' => 'required|integer|exists:income_categories,icid',
            'amount' => 'required|integer|min:1',
            'income_date' => 'required|date',
            'payment_method' => 'required|in:tunai,transfer,lainnya',
            'note' => 'nullable|string|max:1000',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Income::create([
            'icid' => $this->icid,
            'title' => $this->title,
            'amount' => $this->amount,
            'income_date' => $this->income_date,
            'payment_method' => $this->payment_method,
            'note' => $this->note,
            'is_voided' => false,
            'created_by' => Auth::id(),
        ]);

        $this->success('Pemasukan berhasil dicatat.');
        $this->redirectBack('incomes.index');
    }

    public function render()
    {
        return view('livewire.incomes.create', [
            'categories' => IncomeCategory::orderBy('name')->get(),
        ]);
    }
}
