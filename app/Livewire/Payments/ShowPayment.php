<?php

namespace App\Livewire\Payments;

use App\Models\DealerPayment;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class ShowPayment extends Component
{
    use Toast;

    public DealerPayment $payment;

    public bool $showReceipt = false;

    public function mount(DealerPayment $payment): void
    {
        $this->payment = $payment;
    }

    public function openReceipt(): void
    {
        $this->showReceipt = true;
    }

    public function closeReceipt(): void
    {
        $this->showReceipt = false;
    }

    public function render()
    {
        $this->payment->load([
            'dealerBill.dealerStall.dealer',
            'dealerBill.dealerStall.stall',
            'dealerBill.externalDealer.dealer',
            'dealerBill.payments' => fn ($q) => $q->where('is_voided', false),
            'createdBy',
            'voidedBy',
        ]);

        return view('livewire.payments.show');
    }
}
