<?php

namespace App\Livewire\Reports;

use App\Models\Dealer;
use App\Models\DealerBill;
use App\Models\DealerPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class DealerSummary extends Component
{
    use WithPagination;

    public string $search = '';
    public string $from = '';
    public string $to = '';
    public bool $onlyActive = false; // hanya pedagang yang punya tagihan di periode

    public function mount(): void
    {
        $this->from = Carbon::today()->startOfMonth()->format('Y-m-d');
        $this->to   = Carbon::today()->endOfMonth()->format('Y-m-d');
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['search', 'from', 'to', 'onlyActive'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to   = Carbon::parse($this->to)->endOfDay();

        // --- Ambil semua DID yang cocok filter search ---
        $allDids = Dealer::query()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('nik', 'like', "%{$this->search}%")
            ))
            ->pluck('did');

        // --- 4 aggregate queries (stall + external, bills + payments) ---
        $stallBills = DealerBill::join('dealer_stall', 'dealer_bills.dsid', '=', 'dealer_stall.dsid')
            ->whereIn('dealer_stall.did', $allDids)
            ->whereBetween('dealer_bills.due_date', [$from, $to])
            ->whereNotIn('dealer_bills.billing_status', ['cancelled'])
            ->selectRaw('dealer_stall.did as did, COUNT(*) as bill_count, SUM(dealer_bills.total_amount) as total_billed')
            ->groupBy('dealer_stall.did')
            ->get()->keyBy('did');

        $stallPaid = DealerPayment::join('dealer_bills', 'dealer_payment.dbid', '=', 'dealer_bills.dbid')
            ->join('dealer_stall', 'dealer_bills.dsid', '=', 'dealer_stall.dsid')
            ->whereIn('dealer_stall.did', $allDids)
            ->whereBetween('dealer_bills.due_date', [$from, $to])
            ->whereNotIn('dealer_bills.billing_status', ['cancelled'])
            ->where('dealer_payment.is_voided', false)
            ->selectRaw('dealer_stall.did as did, SUM(dealer_payment.paid_amount) as total_paid, MAX(dealer_payment.payment_date) as last_payment_date')
            ->groupBy('dealer_stall.did')
            ->get()->keyBy('did');

        $extBills = DealerBill::join('external_dealers', 'dealer_bills.edid', '=', 'external_dealers.edid')
            ->whereIn('external_dealers.did', $allDids)
            ->whereBetween('dealer_bills.due_date', [$from, $to])
            ->whereNotIn('dealer_bills.billing_status', ['cancelled'])
            ->selectRaw('external_dealers.did as did, COUNT(*) as bill_count, SUM(dealer_bills.total_amount) as total_billed')
            ->groupBy('external_dealers.did')
            ->get()->keyBy('did');

        $extPaid = DealerPayment::join('dealer_bills', 'dealer_payment.dbid', '=', 'dealer_bills.dbid')
            ->join('external_dealers', 'dealer_bills.edid', '=', 'external_dealers.edid')
            ->whereIn('external_dealers.did', $allDids)
            ->whereBetween('dealer_bills.due_date', [$from, $to])
            ->whereNotIn('dealer_bills.billing_status', ['cancelled'])
            ->where('dealer_payment.is_voided', false)
            ->selectRaw('external_dealers.did as did, SUM(dealer_payment.paid_amount) as total_paid, MAX(dealer_payment.payment_date) as last_payment_date')
            ->groupBy('external_dealers.did')
            ->get()->keyBy('did');

        // --- Build summary map per DID ---
        $summaries = $allDids->mapWithKeys(function ($did) use ($stallBills, $stallPaid, $extBills, $extPaid) {
            $billCount   = ($stallBills[$did]->bill_count ?? 0) + ($extBills[$did]->bill_count ?? 0);
            $totalBilled = (float) ($stallBills[$did]->total_billed ?? 0) + (float) ($extBills[$did]->total_billed ?? 0);
            $totalPaid   = (float) ($stallPaid[$did]->total_paid ?? 0) + (float) ($extPaid[$did]->total_paid ?? 0);

            $d1 = $stallPaid[$did]->last_payment_date ?? null;
            $d2 = $extPaid[$did]->last_payment_date ?? null;
            $lastPayment = $d1 && $d2 ? max($d1, $d2) : ($d1 ?? $d2);

            return [$did => [
                'bill_count'   => (int) $billCount,
                'total_billed' => $totalBilled,
                'total_paid'   => $totalPaid,
                'outstanding'  => max($totalBilled - $totalPaid, 0),
                'last_payment' => $lastPayment,
            ]];
        });

        // --- Grand totals (semua DID yang cocok) ---
        $grandBilled      = $summaries->sum('total_billed');
        $grandPaid        = $summaries->sum('total_paid');
        $grandOutstanding = $summaries->sum('outstanding');

        // --- Filter "hanya punya tagihan" ---
        $activeDids = $this->onlyActive
            ? $allDids->filter(fn ($did) => $summaries[$did]['bill_count'] > 0)->values()
            : $allDids;

        // --- Paginate dealers ---
        $dealers = Dealer::query()
            ->with(['dealerStalls' => fn ($q) => $q->where('deleted', false)->with('stall:sid,block')])
            ->whereIn('did', $activeDids)
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.reports.dealer-summary', [
            'dealers'          => $dealers,
            'summaries'        => $summaries,
            'grandBilled'      => $grandBilled,
            'grandPaid'        => $grandPaid,
            'grandOutstanding' => $grandOutstanding,
        ]);
    }
}
