<?php

namespace App\Livewire;

use App\Models\Dealer;
use App\Models\DealerBill;
use App\Models\Stall;
use App\Services\BillGenerationService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function mount(BillGenerationService $bills): void
    {
        // Lazy roll-forward: pastikan tagihan periode berjalan sudah ada saat dashboard dibuka.
        $bills->ensureAllActive();
    }

    public function render()
    {
        $stallActive = Stall::where('is_active', true)->count();
        $stallOccupied = Stall::where('is_active', true)
            ->whereHas('activeRentals')
            ->count();

        $billCounts = DealerBill::selectRaw('billing_status, COUNT(*) as total')
            ->groupBy('billing_status')
            ->pluck('total', 'billing_status');

        $overdue = DealerBill::with(['dealerStall.dealer', 'dealerStall.stall'])
            ->whereDate('due_date', '<=', Carbon::today())
            ->where('billing_status', '!=', 'paid')
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        return view('livewire.dashboard', [
            'stallTotal' => Stall::count(),
            'stallActive' => $stallActive,
            'stallOccupied' => $stallOccupied,
            'stallEmpty' => max($stallActive - $stallOccupied, 0),
            'dealerActive' => Dealer::where('status', 'active')->count(),
            'dealerInactive' => Dealer::where('status', 'inactive')->count(),
            'billPaid' => $billCounts['paid'] ?? 0,
            'billInstallment' => $billCounts['installment'] ?? 0,
            'billUnpaid' => $billCounts['unpaid'] ?? 0,
            'billPending' => $billCounts['pending'] ?? 0,
            'overdue' => $overdue,
        ]);
    }
}
