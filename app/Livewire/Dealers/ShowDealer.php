<?php

namespace App\Livewire\Dealers;

use App\Models\Dealer;
use App\Models\DealerBill;
use App\Models\DealerStall;
use App\Services\BillGenerationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class ShowDealer extends Component
{
    use Toast;

    public Dealer $dealer;

    // --- State modal "Akhiri Sewa" ---
    public bool $endModal = false;
    public ?int $endDsid = null;
    public string $endBlock = '';
    public string $endDate = '';
    public string $arrearAction = 'keep'; // keep | cancel

    public function mount(Dealer $dealer): void
    {
        $this->dealer = $dealer;
    }

    public function startEnd(int $dsid): void
    {
        $ds = $this->dealer->dealerStalls()->where('dsid', $dsid)->first();
        if (! $ds) {
            return;
        }

        $this->endDsid = $dsid;
        $this->endBlock = $ds->stall?->block ?? '-';
        $this->endDate = ($ds->rent_end_date ?? Carbon::today())->toDateString();
        $this->arrearAction = 'keep';
        $this->endModal = true;
    }

    public function endRental(BillGenerationService $bills): void
    {
        $this->validate([
            'endDate' => 'required|date',
            'arrearAction' => 'required|in:keep,cancel',
        ]);

        $ds = DealerStall::where('dsid', $this->endDsid)
            ->where('did', $this->dealer->did)
            ->firstOrFail();

        if (Carbon::parse($this->endDate)->lt(Carbon::parse($ds->rent_start_date))) {
            $this->addError('endDate', 'Tanggal berakhir tidak boleh sebelum tanggal mulai sewa.');

            return;
        }

        DB::transaction(function () use ($ds, $bills) {
            $ds->update([
                'rent_end_date' => $this->endDate,
                'modified_by' => Auth::id(),
            ]);

            // Pastikan tagihan final s/d tanggal berakhir sudah dibuat (engine clamp ke rent_end).
            $bills->ensureBillsUpToDate($ds->fresh());

            // Tunggakan: batalkan bila diminta (tagihan tanpa pembayaran: unpaid/pending).
            if ($this->arrearAction === 'cancel') {
                DealerBill::where('dsid', $ds->dsid)
                    ->whereIn('billing_status', ['unpaid', 'pending'])
                    ->update(['billing_status' => 'cancelled', 'modified_by' => Auth::id()]);
            }
        });

        $this->endModal = false;
        $this->success('Sewa lapak "' . $this->endBlock . '" berhasil diakhiri.');
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
