<?php

namespace App\Livewire\Payments;

use App\Livewire\Concerns\Sortable;
use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Services\BillGenerationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexPayments extends Component
{
    use Sortable;
    use Toast;
    use WithPagination;

    // Filter disimpan di query string (#[Url]) supaya bisa dipulihkan saat
    // kembali dari halaman form (pola "back" — lihat trait ReturnsBack).
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $voidedFilter = '';

    #[Url(except: '')]
    public string $frequencyFilter = '';

    // Nilai = array sqid pedagang (bukan angka) supaya ID asli tidak tampil di URL.
    #[Url(except: [])]
    public array $dealerIds = [];

    // Filter rentang tanggal bayar (kosong = semua).
    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    // Modal kwitansi
    public bool $showReceipt = false;
    public ?int $receiptId = null;

    public function mount(BillGenerationService $bills): void
    {
        $bills->ensureAllActive();
    }

    /** Reset halaman saat filter berubah agar tidak nyangkut di page kosong. */
    public function updated(string $name): void
    {
        if (in_array($name, ['search', 'voidedFilter', 'frequencyFilter', 'from', 'to'], true)
            || str($name)->startsWith('dealerIds')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'voidedFilter', 'frequencyFilter', 'dealerIds', 'from', 'to']);
        $this->resetPage();
    }

    /** Render kwitansi tersembunyi lalu langsung buka dialog cetak browser (tanpa preview). */
    public function openReceipt(int $dpid): void
    {
        $this->receiptId = $dpid;
        $this->showReceipt = true;
        $this->js('window.print()');
    }

    public function closeReceipt(): void
    {
        $this->showReceipt = false;
        $this->receiptId = null;
    }

    /** Kolom sortable (klik header). Kolom relasi pakai subquery. */
    protected function sortColumns(): array
    {
        return [
            'bill_id' => 'bill_id',
            'holder' => "COALESCE(
                (SELECT d.name FROM dealer d JOIN dealer_stall ds ON ds.did = d.did JOIN dealer_bills db ON db.dsid = ds.dsid WHERE db.dbid = dealer_payment.dbid),
                (SELECT d2.name FROM dealer d2 JOIN external_dealers ed ON ed.did = d2.did JOIN dealer_bills db2 ON db2.edid = ed.edid WHERE db2.dbid = dealer_payment.dbid)
            )",
            'bill_type' => '(SELECT db.bill_type FROM dealer_bills db WHERE db.dbid = dealer_payment.dbid)',
            'paid_amount' => 'paid_amount',
            'payment_date' => 'payment_date',
            'payment_method' => 'payment_method',
            'is_voided' => 'is_voided',
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

        $receiptPayment = $this->showReceipt && $this->receiptId
            ? DealerPayment::with(['dealerBill.dealerStall.dealer', 'dealerBill.dealerStall.stall', 'dealerBill.externalDealer.dealer'])
                ->where('is_voided', false)
                ->find($this->receiptId)
            : null;

        $payments = DealerPayment::query()
            ->with(['dealerBill.dealerStall.dealer', 'dealerBill.dealerStall.stall', 'dealerBill.externalDealer.dealer'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('bill_id', 'like', "%{$this->search}%")
                ->orWhereHas('dealerBill.dealerStall.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('dealerBill.externalDealer.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
            ))
            ->when($this->voidedFilter === 'voided', fn ($q) => $q->where('is_voided', true))
            ->when($this->voidedFilter === 'active', fn ($q) => $q->where('is_voided', false))
            ->when($this->from, fn ($q) => $q->whereDate('payment_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('payment_date', '<=', $this->to))
            ->when($this->frequencyFilter, fn ($q) => $q->whereHas('dealerBill', fn ($q2) => $q2->where('frequency', $this->frequencyFilter)))
            ->when($dids, fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('dealerBill.dealerStall', fn ($q2) => $q2->whereIn('did', $dids))
                ->orWhereHas('dealerBill.externalDealer', fn ($q2) => $q2->whereIn('did', $dids))
            ))
;

        $this->applySort($payments, fn ($q) => $q->orderBy('payment_date', 'desc'));

        $payments = $payments->paginate(10);

        return view('livewire.payments.index', [
            'payments' => $payments,
            'receiptPayment' => $receiptPayment,
            // Semua pedagang untuk x-choices-offline (DB-backed, cari & pilih di klien —
            // pola sama seperti "Aturan Bayar" di form lapak). Nilai = sqid (bukan did asli).
            'dealerOptions' => Dealer::orderBy('name')->get()->map(fn ($d) => [
                'id' => $d->obfuscated_id,
                'name' => $d->name,
            ]),
        ]);
    }
}
