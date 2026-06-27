<?php

namespace App\Livewire\Payments;

use App\Models\DealerPayment;
use App\Services\BillGenerationService;
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

    // Modal kwitansi
    public bool $showReceipt = false;
    public ?int $receiptId = null;

    public function mount(BillGenerationService $bills): void
    {
        $bills->ensureAllActive();
    }

    public function openReceipt(int $dpid): void
    {
        $this->receiptId = $dpid;
        $this->showReceipt = true;
    }

    public function closeReceipt(): void
    {
        $this->showReceipt = false;
        $this->receiptId = null;
    }

    public function render()
    {
        $receiptPayment = $this->showReceipt && $this->receiptId
            ? DealerPayment::with(['dealerBill.dealerStall.dealer', 'dealerBill.dealerStall.stall'])
                ->where('is_voided', false)
                ->find($this->receiptId)
            : null;

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
            'receiptPayment' => $receiptPayment,
        ]);
    }
}
