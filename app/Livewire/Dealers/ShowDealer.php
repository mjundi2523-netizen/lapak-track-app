<?php

namespace App\Livewire\Dealers;

use App\Models\Dealer;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class ShowDealer extends Component
{
    use Toast;

    public Dealer $dealer;

    public function mount(Dealer $dealer): void
    {
        $this->dealer = $dealer;
    }

    public function render()
    {
        $this->dealer->load([
            'dealerStalls' => fn ($q) => $q->where('deleted', false),
            'dealerStalls.stall.paymentTerm',
            'dealerStalls.stall.addOns',
            'dealerStalls.bills',
        ]);

        return view('livewire.dealers.show');
    }
}
