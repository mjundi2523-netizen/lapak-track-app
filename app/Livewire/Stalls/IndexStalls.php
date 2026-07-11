<?php

namespace App\Livewire\Stalls;

use App\Livewire\Concerns\Sortable;
use App\Models\Stall;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexStalls extends Component
{
    use Sortable;
    use Toast;
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public function toggleActive(Stall $stall): void
    {
        $stall->update(['is_active' => ! $stall->is_active]);
        $this->success('Status lapak berhasil diubah.');
    }

    /** Kolom sortable (klik header). */
    protected function sortColumns(): array
    {
        return [
            'location' => "CONCAT(block, number)",
            'size' => 'size',
            'term' => '(SELECT MIN(pt.term_name) FROM stall_payment_terms spt JOIN payment_terms pt ON pt.ptid = spt.ptid WHERE spt.sid = stall.sid)',
            'is_active' => 'is_active',
        ];
    }
    public function render()
    {
        $stalls = Stall::query()
            ->with(['paymentTerms'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('block', 'like', "%{$this->search}%")
                ->orWhere('number', 'like', "%{$this->search}%")));

        $this->applySort($stalls, fn ($q) => $q->orderBy('block')->orderBy('number'));

        $stalls = $stalls->paginate(10);

        return view('livewire.stalls.index', [
            'stalls' => $stalls,
        ]);
    }
}
