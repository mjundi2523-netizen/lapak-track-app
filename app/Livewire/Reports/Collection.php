<?php

namespace App\Livewire\Reports;

use App\Models\DealerPayment;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Collection extends Component
{
    use WithPagination;

    public string $from = '';
    public string $to = '';
    public string $search = '';

    public function mount(): void
    {
        $this->from = Carbon::today()->startOfMonth()->toDateString();
        $this->to = Carbon::today()->toDateString();
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['from', 'to', 'search'], true)) {
            $this->resetPage();
        }
    }

    /** Query dasar penerimaan (non-void) sesuai filter; dipakai ulang untuk total & detail. */
    protected function baseQuery()
    {
        return DealerPayment::query()
            ->where('is_voided', false)
            ->when($this->from, fn ($q) => $q->whereDate('payment_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('payment_date', '<=', $this->to))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('bill_id', 'like', "%{$this->search}%")
                ->orWhereHas('dealerBill.dealerStall.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('dealerBill.externalDealer.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
            ));
    }

    public function render()
    {
        // Total keseluruhan + rincian per metode (ikut filter, kecuali metode itu sendiri).
        $byMethod = $this->baseQuery()
            ->selectRaw('payment_method, COUNT(*) c, COALESCE(SUM(paid_amount), 0) t')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $tunaiTotal = (float) ($byMethod['tunai']->t ?? 0);
        $tunaiCount = (int) ($byMethod['tunai']->c ?? 0);
        $transferTotal = (float) ($byMethod['transfer']->t ?? 0);
        $transferCount = (int) ($byMethod['transfer']->c ?? 0);

        $grandTotal = $byMethod->sum('t');
        $grandCount = $byMethod->sum('c');

        $payments = $this->baseQuery()
            ->with(['dealerBill.dealerStall.dealer', 'dealerBill.dealerStall.stall', 'dealerBill.externalDealer.dealer'])
            ->orderByDesc('payment_date')
            ->orderByDesc('dpid')
            ->paginate(15);

        return view('livewire.reports.collection', [
            'payments' => $payments,
            'grandTotal' => $grandTotal,
            'grandCount' => $grandCount,
            'tunaiTotal' => $tunaiTotal,
            'tunaiCount' => $tunaiCount,
            'transferTotal' => $transferTotal,
            'transferCount' => $transferCount,
        ]);
    }
}
