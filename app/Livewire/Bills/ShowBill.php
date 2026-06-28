<?php

namespace App\Livewire\Bills;

use App\Models\DealerBill;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class ShowBill extends Component
{
    use Toast;

    public DealerBill $dealerBill;

    public function mount(DealerBill $dealerBill): void
    {
        $this->dealerBill = $dealerBill;
    }

    public function recalculate(): void
    {
        $this->dealerBill->recalculateBillingStatus();
        $this->success('Status tagihan berhasil dihitung ulang.');
    }

    public function render()
    {
        $this->dealerBill->load([
            'dealerStall.dealer',
            'dealerStall.stall.paymentTerm',
            'dealerStall.stall.addOns',
            'externalDealer.dealer',
            'externalDealer.paymentTerm',
            'addOn',
            'payments' => fn ($q) => $q->orderBy('payment_date', 'desc'),
            'payments.voidedBy',
            'payments.createdBy',
        ]);

        return view('livewire.bills.show');
    }
}
