<?php

namespace App\Livewire\Bills;

use App\Models\Dealer;
use App\Models\DealerBill;
use App\Services\BillGenerationService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexBills extends Component
{
    use Toast;
    use WithPagination;

    // Filter disimpan di query string (#[Url]) supaya bisa dipulihkan saat
    // kembali dari halaman form (pola "back" — lihat trait ReturnsBack).
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    #[Url(except: '')]
    public string $frequencyFilter = '';

    #[Url(except: null)]
    public ?int $dealerId = null;

    // Filter rentang tanggal jatuh tempo (kosong = semua).
    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    /** Kandidat opsi untuk autocomplete pedagang (x-choices searchable). */
    public Collection $dealersList;

    public function mount(BillGenerationService $bills): void
    {
        $bills->ensureAllActive();
        $this->searchDealer();
    }

    /** Reset halaman saat filter berubah agar tidak nyangkut di page kosong. */
    public function updated(string $name): void
    {
        if (in_array($name, ['search', 'statusFilter', 'frequencyFilter', 'dealerId', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'frequencyFilter', 'dealerId', 'from', 'to']);
        $this->resetPage();
        $this->searchDealer();
    }

    /** Dipanggil x-choices saat user mengetik; jaga pedagang terpilih tetap muncul. */
    public function searchDealer(string $value = ''): void
    {
        $selected = $this->dealerId
            ? Dealer::where('did', $this->dealerId)->get()
            : collect();

        $this->dealersList = Dealer::query()
            ->when($value, fn ($q) => $q->where('name', 'like', "%{$value}%"))
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->merge($selected)
            ->unique('did')
            ->values();
    }

    public function render()
    {
        $query = DealerBill::query()
            ->with([
                'dealerStall.dealer',
                'dealerStall.stall',
                'externalDealer.dealer',
                'payments' => fn ($q) => $q->where('is_voided', false),
            ])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('bill_id', 'like', "%{$this->search}%")
                ->orWhereHas('dealerStall.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('externalDealer.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
            ))
            ->when($this->statusFilter, fn ($q) => $q->where('billing_status', $this->statusFilter))
            ->when($this->frequencyFilter, fn ($q) => $q->where('frequency', $this->frequencyFilter))
            ->when($this->from, fn ($q) => $q->whereDate('due_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('due_date', '<=', $this->to))
            ->when($this->dealerId, fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('dealerStall', fn ($q2) => $q2->where('did', $this->dealerId))
                ->orWhereHas('externalDealer', fn ($q2) => $q2->where('did', $this->dealerId))
            ))
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
