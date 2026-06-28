<?php

namespace App\Livewire\Stalls;

use App\Models\Stall;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexStalls extends Component
{
    use Toast;
    use WithPagination;

    public string $search = '';

    public function toggleActive(Stall $stall): void
    {
        $stall->update(['is_active' => ! $stall->is_active]);
        $this->success('Status lapak berhasil diubah.');
    }

    public function render()
    {
        $stalls = Stall::query()
            ->with(['paymentTerm'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('block', 'like', "%{$this->search}%")
                ->orWhere('number', 'like', "%{$this->search}%")))
            ->orderBy('block')
            ->orderBy('number')
            ->paginate(10);

        return view('livewire.stalls.index', [
            'stalls' => $stalls,
        ]);
    }
}
