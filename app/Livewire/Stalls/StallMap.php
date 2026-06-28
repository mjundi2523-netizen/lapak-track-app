<?php

namespace App\Livewire\Stalls;

use App\Models\DealerStall;
use App\Models\Stall;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StallMap extends Component
{
    public function render()
    {
        $today = Carbon::today()->toDateString();

        // Occupancy dalam satu query: rental aktif (belum dihapus, window sewa
        // mencakup hari ini — eksklusif di rent_end_date). Keyed per sid → tenant terkini.
        $occupied = DealerStall::with('dealer:did,name')
            ->where('deleted', false)
            ->whereDate('rent_start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('rent_end_date')
                    ->orWhereDate('rent_end_date', '>', $today);
            })
            ->orderByDesc('rent_start_date')
            ->get()
            ->groupBy('sid')
            ->map(fn ($rentals) => $rentals->first());

        $stalls = Stall::orderBy('block')->get();

        // Susun jadi denah: dikelompokkan per blok (mis. "A01"), tiap sel = nomor lapak.
        $rows = $stalls
            ->groupBy(fn ($s) => $s->block ?: 'Lainnya')
            ->map(function ($group) use ($occupied) {
                return $group->sortBy('number')->map(function ($s) use ($occupied) {
                    $tenant = $occupied->get($s->sid);

                    $status = ! $s->is_active
                        ? 'inactive'
                        : ($tenant ? 'occupied' : 'empty');

                    return [
                        'sid'    => $s->sid,
                        'number' => $s->number,
                        'size'   => $s->size,
                        'status' => $status,
                        'tenant' => $tenant?->dealer?->name,
                    ];
                })->values();
            });

        $activeStalls = $stalls->where('is_active', true);
        $occupiedCount = $activeStalls->filter(fn ($s) => $occupied->has($s->sid))->count();

        return view('livewire.stalls.map', [
            'rows'          => $rows,
            'total'         => $stalls->count(),
            'occupiedCount' => $occupiedCount,
            'emptyCount'    => max($activeStalls->count() - $occupiedCount, 0),
            'inactiveCount' => $stalls->where('is_active', false)->count(),
        ]);
    }
}
