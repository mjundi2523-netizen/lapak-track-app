<?php

namespace App\Livewire\Bills;

use App\Models\DealerBill;
use App\Services\BillGenerationService;
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

    public function mount(BillGenerationService $bills): void
    {
        $bills->ensureAllActive();
    }

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

        // Terbayar/Sisa dihitung langsung dari relasi `payments` (sudah di-eager-load
        // & difilter is_voided=false) — sumber yang sama dengan halaman detail,
        // jadi nilainya dijamin konsisten.
        return view('livewire.bills.index', [
            'bills' => $bills,
        ]);
    }
}
