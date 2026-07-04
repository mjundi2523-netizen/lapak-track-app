<?php

namespace App\Livewire\Payments;

use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Services\BillGenerationService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexPayments extends Component
{
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

    #[Url(except: null)]
    public ?int $dealerId = null;

    // Filter rentang tanggal bayar (kosong = semua).
    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    /** Kandidat opsi untuk autocomplete pedagang (x-choices searchable). */
    public Collection $dealersList;

    // Modal kwitansi
    public bool $showReceipt = false;
    public ?int $receiptId = null;

    public function mount(BillGenerationService $bills): void
    {
        $bills->ensureAllActive();
        $this->searchDealer();
    }

    /** Reset halaman saat filter berubah agar tidak nyangkut di page kosong. */
    public function updated(string $name): void
    {
        if (in_array($name, ['search', 'voidedFilter', 'frequencyFilter', 'dealerId', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'voidedFilter', 'frequencyFilter', 'dealerId', 'from', 'to']);
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

    public function render()
    {
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
            ->when($this->dealerId, fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('dealerBill.dealerStall', fn ($q2) => $q2->where('did', $this->dealerId))
                ->orWhereHas('dealerBill.externalDealer', fn ($q2) => $q2->where('did', $this->dealerId))
            ))
            ->orderBy('payment_date', 'desc')
            ->paginate(10);

        return view('livewire.payments.index', [
            'payments' => $payments,
            'receiptPayment' => $receiptPayment,
        ]);
    }
}
