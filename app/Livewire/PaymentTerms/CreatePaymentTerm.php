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
class CreatePaymentTerm extends Component
{
    use Toast;

    #[Validate('required|string|max:255|unique:payment_terms,term_name')]
    public string $term_name = '';

    #[Validate('required|in:daily,weekly,monthly,annual')]
    public string $frequency = 'monthly';

    #[Validate('required|integer|min:1')]
    public int $interval_count = 1;

    #[Validate('required|integer|min:0')]
    public int $price = 0;

    public bool $is_new = false;

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            PaymentTerm::create([
                'term_name' => $this->term_name,
                'frequency' => $this->frequency,
                'interval_count' => $this->interval_count,
                'is_new' => $this->is_new,
                'price' => $this->price,
                'created_by' => Auth::id(),
            ]);
        });

        $this->success('Aturan bayar berhasil ditambahkan.');
        $this->redirect(route('payment-terms.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.payment-terms.create');
    }
}
