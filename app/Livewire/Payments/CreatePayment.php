<?php

namespace App\Livewire\Payments;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\DealerBill;
use App\Models\DealerPayment;
use App\Services\BillIdGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class CreatePayment extends Component
{
    use ReturnsBack;
    use Toast;

    public ?int $selectedDbid = null;
    public string $billSearch = '';
    public bool $showBillModal = false;

    #[Validate('required|numeric|min:0.01')]
    public float $paid_amount = 0;

    #[Validate('required|date|before_or_equal:today')]
    public string $payment_date = '';

    #[Validate('required|in:tunai,transfer,lainnya')]
    public string $payment_method = 'tunai';

    /** Checkbox "Lunasi": nominal dikunci = sisa tagihan (input nominal di-disable). */
    public bool $payInFull = false;

    public function mount(): void
    {
        $this->payment_date = Carbon::now()->format('Y-m-d');

        // Auto-select bill from URL ?bill=X
        if (request()->has('bill')) {
            $bill = DealerBill::where('dbid', request('bill'))->first();
            if ($bill) {
                $this->selectedDbid = $bill->dbid;
            }
        }
    }

    public function selectBill(int $dbid): void
    {
        $this->selectedDbid = $dbid;
        $this->showBillModal = false;

        // Mode "Lunasi" aktif → sinkronkan nominal ke sisa tagihan yang baru dipilih.
        if ($this->payInFull) {
            $this->syncFullAmount();
        }
    }

    public function clearBill(): void
    {
        $this->selectedDbid = null;
        $this->payInFull = false;
    }

    /** Sisa tagihan terpilih = total - Σ pembayaran non-void. */
    protected function remainingFor(DealerBill $bill): float
    {
        $paid = (float) $bill->payments()->where('is_voided', false)->sum('paid_amount');

        return max((float) $bill->total_amount - $paid, 0);
    }

    public function updatedPayInFull(bool $value): void
    {
        if ($value) {
            $this->syncFullAmount();
        }
    }

    /** Set nominal = sisa tagihan terpilih (dipakai mode "Lunasi"). */
    protected function syncFullAmount(): void
    {
        if (! $this->selectedDbid) {
            return;
        }

        $bill = DealerBill::find($this->selectedDbid);
        if ($bill) {
            $this->paid_amount = $this->remainingFor($bill);
        }
    }

    public function save(BillIdGenerator $generator): void
    {
        $this->validate();

        if (! $this->selectedDbid) {
            $this->error('Pilih tagihan terlebih dahulu.');

            return;
        }

        $bill = DealerBill::findOrFail($this->selectedDbid);

        // Tidak bisa membayar tagihan yang sudah lunas / dibatalkan.
        if (in_array($bill->billing_status, ['paid', 'cancelled'], true)) {
            $this->addError('paid_amount', 'Tagihan ini sudah lunas atau dibatalkan, tidak bisa dibayar.');

            return;
        }

        // Mode "Lunasi": kunci nominal ke sisa tagihan TERKINI (hindari state basi).
        if ($this->payInFull) {
            $this->paid_amount = $this->remainingFor($bill);
        }

        // Blocking: nominal tidak boleh melebihi sisa tagihan.
        $remaining = $this->remainingFor($bill);
        if ($this->paid_amount > $remaining + 0.001) {
            $this->addError('paid_amount', 'Nominal melebihi sisa tagihan. Maksimal Rp ' . number_format($remaining, 0, ',', '.') . '.');

            return;
        }

        DB::transaction(function () use ($generator, $bill) {
            $billId = $generator->generate('dealer_payment', 'PMT', Carbon::parse($this->payment_date));

            DealerPayment::create([
                'bill_id' => $billId,
                'dbid' => $bill->dbid,
                'paid_amount' => $this->paid_amount,
                'payment_date' => $this->payment_date,
                'payment_method' => $this->payment_method,
                'is_voided' => false,
                'created_by' => Auth::id(),
            ]);

            $bill->recalculateBillingStatus();
        });

        $this->success('Pembayaran berhasil ditambahkan.');
        $this->redirectBack('payments.index');
    }

    public function render()
    {
        $selectedBill = null;
        $remaining = null;
        if ($this->selectedDbid) {
            $selectedBill = DealerBill::with(['dealerStall.dealer', 'dealerStall.stall', 'externalDealer.dealer', 'payments' => fn ($q) => $q->where('is_voided', false)])
                ->find($this->selectedDbid);
            if ($selectedBill) {
                $remaining = max((float) $selectedBill->total_amount - (float) $selectedBill->payments->sum('paid_amount'), 0);
            }
        }

        $billResults = DealerBill::query()
            ->with(['dealerStall.dealer', 'dealerStall.stall', 'externalDealer.dealer'])
            ->whereNotIn('billing_status', ['paid', 'cancelled'])
            ->when($this->billSearch, fn ($q) => $q
                ->where('bill_id', 'like', "%{$this->billSearch}%")
                ->orWhereHas('dealerStall.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->billSearch}%"))
                ->orWhereHas('externalDealer.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->billSearch}%"))
            )
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        return view('livewire.payments.create', [
            'selectedBill' => $selectedBill,
            'remaining' => $remaining,
            'billResults' => $billResults,
        ]);
    }
}
