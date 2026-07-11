<?php

namespace App\Livewire\Dealers;

use App\Models\Dealer;
use App\Models\DealerBill;
use App\Models\DealerStall;
use App\Models\ExternalDealer;
use App\Services\BillGenerationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class ShowDealer extends Component
{
    use Toast, WithPagination;

    public Dealer $dealer;

    // --- State modal "Akhiri Sewa" ---
    public bool $endModal = false;
    public ?int $endDsid = null;
    public string $endBlock = '';
    public string $endDate = '';
    public string $arrearAction = 'keep'; // keep | cancel

    // --- State modal "Hapus Rental (Salah Input)" ---
    public bool $deleteModal = false;
    public ?int $deleteDsid = null;
    public string $deleteBlock = '';

    // --- State modal "Akhiri Kontrak" (eksternal) ---
    public bool $endExtModal = false;
    public ?int $endExtEdid = null;
    public string $endExtTerm = '';
    public string $endExtDate = '';
    public string $extArrearAction = 'keep'; // keep | cancel

    // --- State modal "Cetak Surat Pedagang" ---
    public bool $showLetter = false;

    public function mount(Dealer $dealer): void
    {
        $this->dealer = $dealer;
    }

    /** Render surat tersembunyi lalu langsung buka dialog cetak browser (tanpa preview). */
    public function openLetter(): void
    {
        $this->showLetter = true;
        $this->js('window.print()');
    }

    public function startEnd(int $dsid): void
    {
        $ds = $this->dealer->dealerStalls()->where('dsid', $dsid)->first();
        if (! $ds) {
            return;
        }

        $this->endDsid = $dsid;
        $this->endBlock = $ds->stall?->code ?? '-';
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

    public function startEndExternal(int $edid): void
    {
        $ed = $this->dealer->externalDealers()->where('edid', $edid)->first();
        if (! $ed) {
            return;
        }

        $this->endExtEdid = $edid;
        $this->endExtTerm = $ed->paymentTerm?->term_name ?? '-';
        $this->endExtDate = ($ed->end_date ?? Carbon::today())->toDateString();
        $this->extArrearAction = 'keep';
        $this->endExtModal = true;
    }

    public function endExternal(BillGenerationService $bills): void
    {
        $this->validate([
            'endExtDate' => 'required|date',
            'extArrearAction' => 'required|in:keep,cancel',
        ]);

        $ed = ExternalDealer::where('edid', $this->endExtEdid)
            ->where('did', $this->dealer->did)
            ->firstOrFail();

        if (Carbon::parse($this->endExtDate)->lt(Carbon::parse($ed->start_date))) {
            $this->addError('endExtDate', 'Tanggal berakhir tidak boleh sebelum tanggal mulai kontrak.');

            return;
        }

        DB::transaction(function () use ($ed, $bills) {
            $ed->update(['end_date' => $this->endExtDate, 'modified_by' => Auth::id()]);
            $bills->ensureExternalBillsUpToDate($ed->fresh());

            if ($this->extArrearAction === 'cancel') {
                DealerBill::where('edid', $ed->edid)
                    ->whereIn('billing_status', ['unpaid', 'pending'])
                    ->update(['billing_status' => 'cancelled', 'modified_by' => Auth::id()]);
            }
        });

        $this->endExtModal = false;
        $this->success('Kontrak eksternal "' . $this->endExtTerm . '" berhasil diakhiri.');
    }

    public function startDelete(int $dsid): void
    {
        $ds = $this->dealer->dealerStalls()->where('dsid', $dsid)->first();
        if (! $ds) {
            return;
        }
        $this->deleteDsid  = $dsid;
        $this->deleteBlock = $ds->stall?->code ?? '-';
        $this->deleteModal = true;
    }

    public function deleteRental(): void
    {
        $ds = DealerStall::where('dsid', $this->deleteDsid)
            ->where('did', $this->dealer->did)
            ->firstOrFail();

        // Cegah penghapusan jika sudah ada pembayaran (bukan salah input murni).
        $hasPaid = DealerBill::where('dsid', $ds->dsid)
            ->whereHas('payments', fn ($q) => $q->where('is_voided', false))
            ->exists();

        if ($hasPaid) {
            $this->addError('deleteRental', 'Tidak bisa dihapus — sudah ada pembayaran yang tercatat untuk rental ini. Gunakan "Akhiri Sewa" sebagai gantinya.');

            return;
        }

        DB::transaction(function () use ($ds) {
            // Batalkan semua tagihan yang belum dibayar.
            DealerBill::where('dsid', $ds->dsid)
                ->whereIn('billing_status', ['unpaid', 'pending'])
                ->update(['billing_status' => 'cancelled', 'modified_by' => Auth::id()]);

            // Soft-delete record rental.
            $ds->update(['deleted' => true, 'modified_by' => Auth::id()]);
        });

        $this->deleteModal = false;
        $this->success('Rental lapak "' . $this->deleteBlock . '" berhasil dihapus dari sistem.');
    }

    public function render()
    {
        $this->dealer->load([
            'dealerStalls' => fn ($q) => $q->where('deleted', false),
            'dealerStalls.stall.addOns',
            'dealerStalls.stallPaymentTerm.paymentTerm',
            'externalDealers' => fn ($q) => $q->where('deleted', false),
            'externalDealers.paymentTerm',
        ]);

        $bills = DealerBill::query()
            ->where(fn ($q) => $q
                ->whereHas('dealerStall', fn ($q2) => $q2->where('did', $this->dealer->did))
                ->orWhereHas('externalDealer', fn ($q2) => $q2->where('did', $this->dealer->did))
            )
            ->with(['dealerStall.stall:sid,block', 'externalDealer'])
            ->orderByDesc('due_date')
            ->paginate(15, pageName: 'bills');

        return view('livewire.dealers.show', compact('bills'));
    }
}
