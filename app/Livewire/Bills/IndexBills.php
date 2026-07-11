<?php

namespace App\Livewire\Bills;

use App\Livewire\Concerns\Sortable;
use App\Models\Dealer;
use App\Models\DealerBill;
use App\Services\BillGenerationService;
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

    // Nilai = array sqid pedagang (bukan angka) supaya ID asli tidak tampil di URL.
    #[Url(except: [])]
    public array $dealerIds = [];

    // Filter rentang tanggal jatuh tempo (kosong = semua).
    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function mount(BillGenerationService $bills): void
    {
        $bills->ensureAllActive();
    }

    /** Reset halaman saat filter berubah agar tidak nyangkut di page kosong. */
    public function updated(string $name): void
    {
        if (in_array($name, ['search', 'statusFilter', 'frequencyFilter', 'from', 'to'], true)
            || str($name)->startsWith('dealerIds')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'frequencyFilter', 'dealerIds', 'from', 'to']);
        $this->resetPage();
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

    public function render()
    {
        // Decode sqid → did asli untuk filter (bisa >1 pedagang).
        $dids = collect($this->dealerIds)
            ->map(fn ($k) => Dealer::decodeKey($k))
            ->filter()
            ->values()
            ->all();

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
            ->when($dids, fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('dealerStall', fn ($q2) => $q2->whereIn('did', $dids))
                ->orWhereHas('externalDealer', fn ($q2) => $q2->whereIn('did', $dids))
            ));

        $this->applySort($query, fn ($q) => $q->orderBy('created_at', 'desc'));

        $bills = $query->paginate(10);

        // Terbayar/Sisa dihitung langsung dari relasi `payments` (sudah di-eager-load
        // & difilter is_voided=false) — sumber yang sama dengan halaman detail,
        // jadi nilainya dijamin konsisten.
        return view('livewire.bills.index', [
            'bills' => $bills,
            // Semua pedagang untuk x-choices-offline (DB-backed, cari & pilih di klien —
            // pola sama seperti "Aturan Bayar" di form lapak). Nilai = sqid (bukan did asli).
            'dealerOptions' => Dealer::orderBy('name')->get()->map(fn ($d) => [
                'id' => $d->obfuscated_id,
                'name' => $d->name,
            ]),
        ]);
    }
}
