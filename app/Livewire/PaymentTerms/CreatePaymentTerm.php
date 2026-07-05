<?php

namespace App\Livewire\PaymentTerms;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\PaymentTerm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class CreatePaymentTerm extends Component
{
    use ReturnsBack;
    use Toast;

    // Unik per-market → divalidasi di save() (butuh market_id user).
    public string $term_name = '';

    #[Validate('required|in:daily,weekly,monthly,annual')]
    public string $frequency = 'monthly';

    #[Validate('required|integer|min:1')]
    public int $interval_count = 1;

    #[Validate('required|integer|min:0')]
    public int $price = 0;

    // Kondisi pedagang sasaran aturan ini (regular default; 2 checkbox mutually-exclusive).
    public bool $cond_new = false;
    public bool $cond_external = false;

    public function updatedCondNew($value): void
    {
        if ($value) {
            $this->cond_external = false;
        }
    }

    public function updatedCondExternal($value): void
    {
        // Fitur "pedagang eksternal" = premium.
        if ($value && ! auth()->user()->isPremium()) {
            $this->cond_external = false;
            $this->dispatch('premium-required');

            return;
        }

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
        // Guard premium: cegah pembuatan aturan bayar eksternal oleh akun non-premium.
        if ($this->cond_external && ! auth()->user()->isPremium()) {
            $this->cond_external = false;
            $this->dispatch('premium-required');

            return;
        }

        $this->validate([
            'term_name' => ['required', 'string', 'max:255',
                Rule::unique('payment_terms', 'term_name')->where('market_id', Auth::user()->market_id)],
        ]);
        $this->validate();

        DB::transaction(function () {
            PaymentTerm::create([
                'term_name' => $this->term_name,
                'frequency' => $this->frequency,
                'interval_count' => $this->interval_count,
                'dealer_condition' => $this->dealerCondition(),
                'price' => $this->price,
                'created_by' => Auth::id(),
            ]);
        });

        $this->success('Aturan bayar berhasil ditambahkan.');
        $this->redirectBack('payment-terms.index');
    }

    public function render()
    {
        return view('livewire.payment-terms.create');
    }
}
