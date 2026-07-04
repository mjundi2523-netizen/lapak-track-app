<?php

namespace App\Livewire\Payments;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\DealerPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class VoidPayment extends Component
{
    use ReturnsBack;
    use Toast;

    public DealerPayment $payment;

    #[Validate('required|string|max:1000')]
    public string $voided_reason = '';

    public function mount(DealerPayment $payment): void
    {
        $this->payment = $payment->load(['dealerBill.dealerStall.dealer', 'dealerBill.dealerStall.stall', 'dealerBill.externalDealer.dealer']);
    }

    public function void(): void
    {
        $this->validate();

        DB::transaction(function () {
            $this->payment->update([
                'is_voided' => true,
                'voided_reason' => $this->voided_reason,
                'voided_at' => Carbon::now(),
                'voided_by' => Auth::id(),
                'modified_by' => Auth::id(),
            ]);

            $this->payment->dealerBill->recalculateBillingStatus();
        });

        $this->success('Pembayaran berhasil dibatalkan.');
        $this->redirectBack('payments.index');
    }

    public function cancel(): void
    {
        $this->redirectBack('payments.index');
    }

    public function render()
    {
        return view('livewire.payments.void');
    }
}
