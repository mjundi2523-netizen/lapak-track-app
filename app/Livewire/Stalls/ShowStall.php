<?php

namespace App\Livewire\Stalls;

use App\Models\DealerBill;
use App\Models\Stall;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ShowStall extends Component
{
    use WithPagination;

    public Stall $stall;

    public function mount(Stall $stall): void
    {
        $this->stall = $stall;
    }

    public function render()
    {
        $this->stall->load('paymentTerms');

        // Penyewa aktif terkini (rental belum dihapus, mulai sewa terbaru).
        $tenant = $this->stall->activeRentals()
            ->with('dealer')
            ->orderByDesc('rent_start_date')
            ->first();

        // Semua tagihan untuk lapak ini (lewat dealer_stall), terbaru dulu.
        $bills = DealerBill::with('dealerStall.dealer')
            ->whereHas('dealerStall', fn ($q) => $q->where('sid', $this->stall->sid))
            ->orderByDesc('due_date')
            ->paginate(10);

        return view('livewire.stalls.show', [
            'tenant' => $tenant,
            'bills' => $bills,
        ]);
    }
}
