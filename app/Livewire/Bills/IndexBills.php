<?php

namespace App\Livewire\Bills;

use App\Models\DealerBill;
use App\Models\DealerPayment;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexBills extends Component
{
    use Toast;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function render()
    {
        $query = DealerBill::query()
            ->with([
                'dealerStall.dealer',
                'dealerStall.stall',
                'payments' => fn ($q) => $q->where('is_voided', false),
            ])
            ->when($this->search, fn ($q) => $q
                ->where('bill_id', 'like', "%{$this->search}%")
                ->orWhereHas('dealerStall.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
            )
            ->when($this->statusFilter, fn ($q) => $q->where('billing_status', $this->statusFilter))
            ->orderBy('created_at', 'desc');

        $bills = $query->paginate(10);

        // Batched paid-totals query
        $billIds = $bills->pluck('dbid')->toArray();
        $paidTotals = DealerPayment::selectRaw('dbid, SUM(paid_amount) as total')
            ->whereIn('dbid', $billIds)
            ->where('is_voided', false)
            ->groupBy('dbid')
            ->pluck('total', 'dbid');

        return view('livewire.bills.index', [
            'bills' => $bills,
            'paidTotals' => $paidTotals,
        ]);
    }
}
