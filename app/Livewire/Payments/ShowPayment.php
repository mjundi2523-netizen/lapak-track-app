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

    /** Render kwitansi tersembunyi lalu langsung buka dialog cetak browser (tanpa preview). */
    public function openReceipt(): void
    {
        $this->showReceipt = true;
        $this->js('window.print()');
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
