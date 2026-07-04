<?php

namespace App\Livewire\PaymentTerms;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\PaymentTerm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class EditPaymentTerm extends Component
{
    use ReturnsBack;
    use Toast;

    public PaymentTerm $paymentTerm;

    #[Validate('required|string|max:255')]
    public string $term_name = '';

    #[Validate('required|in:daily,weekly,monthly,annual')]
    public string $frequency = '';

    #[Validate('required|integer|min:1')]
    public int $interval_count = 1;

    #[Validate('required|integer|min:0')]
    public int $price = 0;

    public bool $cond_new = false;
    public bool $cond_external = false;

    public function mount(PaymentTerm $paymentTerm): void
    {
        $this->paymentTerm = $paymentTerm;
        $this->term_name = $paymentTerm->term_name;
        $this->frequency = $paymentTerm->frequency;
        $this->interval_count = $paymentTerm->interval_count ?? 1;
        $this->cond_new = $paymentTerm->dealer_condition === 'new';
        $this->cond_external = $paymentTerm->dealer_condition === 'external';
        $this->price = $paymentTerm->price;
    }

    public function updatedCondNew($value): void
    {
        if ($value) {
            $this->cond_external = false;
        }
    }

    public function updatedCondExternal($value): void
    {
        if ($value) {
            $this->cond_new = false;
        }
    }

    protected function dealerCondition(): string
    {
        return $this->cond_external ? 'external' : ($this->cond_new ? 'new' : 'regular');
    }

    public function save(): void
    {
        $this->validate([
            'term_name' => 'required|string|max:255|unique:payment_terms,term_name,' . $this->paymentTerm->ptid . ',ptid',
            'frequency' => 'required|in:daily,weekly,monthly,annual',
            'interval_count' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
        ]);

        DB::transaction(function () {
            $this->paymentTerm->update([
                'term_name' => $this->term_name,
                'frequency' => $this->frequency,
                'interval_count' => $this->interval_count,
                'dealer_condition' => $this->dealerCondition(),
                'price' => $this->price,
                'modified_by' => Auth::id(),
            ]);
        });

        $this->success('Aturan bayar berhasil diperbarui.');
        $this->redirectBack('payment-terms.index');
    }

    public function render()
    {
        return view('livewire.payment-terms.edit');
    }
}
