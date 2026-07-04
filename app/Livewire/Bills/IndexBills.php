<?php

namespace App\Livewire\Bills;

use App\Livewire\Concerns\Sortable;
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
    use Sortable;
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

    /** Kolom sortable (klik header). Kolom relasi/terhitung pakai subquery. */
    protected function sortColumns(): array
    {
        $paidSub = '(SELECT COALESCE(SUM(dp.paid_amount), 0) FROM dealer_payment dp WHERE dp.dbid = dealer_bills.dbid AND dp.is_voided = 0)';

        return [
            'bill_id' => 'bill_id',
            'bill_type' => 'bill_type',
            'holder' => "COALESCE(
                (SELECT d.name FROM dealer d JOIN dealer_stall ds ON ds.did = d.did WHERE ds.dsid = dealer_bills.dsid),
                (SELECT d2.name FROM dealer d2 JOIN external_dealers ed ON ed.did = d2.did WHERE ed.edid = dealer_bills.edid)
            )",
            'location' => "(SELECT CONCAT(s.block, '/', s.number) FROM stall s JOIN dealer_stall ds2 ON ds2.sid = s.sid WHERE ds2.dsid = dealer_bills.dsid)",
            'total_amount' => 'total_amount',
            'paid' => $paidSub,
            'remaining' => "total_amount - {$paidSub}",
            'due_date' => 'due_date',
            'billing_status' => 'billing_status',
        ];
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
            ));

        $this->applySort($query, fn ($q) => $q->orderBy('created_at', 'desc'));

        $bills = $query->paginate(10);

        // Terbayar/Sisa dihitung langsung dari relasi `payments` (sudah di-eager-load
        // & difilter is_voided=false) — sumber yang sama dengan halaman detail,
        // jadi nilainya dijamin konsisten.
        return view('livewire.bills.index', [
            'bills' => $bills,
        ]);
    }
}
