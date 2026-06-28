<?php

namespace App\Livewire;

use App\Models\DealerBill;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class NotificationBell extends Component
{
    public function render()
    {
        $today = Carbon::today();

        $alerts = DealerBill::query()
            ->whereIn('billing_status', ['unpaid', 'pending'])
            ->whereDate('due_date', '<=', $today)
            ->whereNotNull('due_date')
            ->with([
                'dealerStall.dealer:did,name',
                'dealerStall.stall:sid,block',
                'externalDealer.dealer:did,name',
            ])
            ->orderBy('due_date')
            ->get();

        $overdueItems = $alerts->filter(fn ($b) => $b->due_date && $b->due_date->lt($today))->take(10);
        $todayItems   = $alerts->filter(fn ($b) => $b->due_date && $b->due_date->isToday())->take(5);
        $totalCount   = $alerts->count();

        return view('livewire.notification-bell', compact('overdueItems', 'todayItems', 'totalCount'));
    }
}
