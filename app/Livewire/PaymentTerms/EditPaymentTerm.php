<?php

namespace App\Livewire\PaymentTerms;

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

    public function mount(PaymentTerm $paymentTerm): void
    {
        $this->paymentTerm = $paymentTerm;
        $this->term_name = $paymentTerm->term_name;
        $this->frequency = $paymentTerm->frequency;
        $this->interval_count = $paymentTerm->interval_count ?? 1;
        $this->price = $paymentTerm->price;
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
                'price' => $this->price,
                'modified_by' => Auth::id(),
            ]);
        });

        $this->success('Aturan bayar berhasil diperbarui.');
        $this->redirect(route('payment-terms.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.payment-terms.edit');
    }
}
