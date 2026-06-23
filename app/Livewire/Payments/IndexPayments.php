<?php

namespace App\Livewire\Payments;

use App\Models\DealerPayment;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexPayments extends Component
{
    use Toast;
    use WithPagination;

    public string $search = '';
    public string $voidedFilter = '';

    public function render()
    {
        $payments = DealerPayment::query()
            ->with(['dealerBill.dealerStall.dealer', 'dealerBill.dealerStall.stall'])
            ->when($this->search, fn ($q) => $q
                ->where('bill_id', 'like', "%{$this->search}%")
                ->orWhereHas('dealerBill.dealerStall.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
            )
            ->when($this->voidedFilter === 'voided', fn ($q) => $q->where('is_voided', true))
            ->when($this->voidedFilter === 'active', fn ($q) => $q->where('is_voided', false))
            ->orderBy('payment_date', 'desc')
            ->paginate(10);

        return view('livewire.payments.index', [
            'payments' => $payments,
        ]);
    }
}
