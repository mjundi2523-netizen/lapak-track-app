<?php

namespace App\Livewire\Stalls;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\AddOn;
use App\Models\DealerStall;
use App\Models\PaymentTerm;
use App\Models\Stall;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class EditStall extends Component
{
    use ReturnsBack;
    use Toast;

    public Stall $stall;

    public string $block = '';

    public string $number = '';

    public ?string $description = null;

    public ?string $size = null;

    /** Aturan bayar yang ditawarkan lapak (bisa >1). Sinkron ke pivot stall_payment_terms. */
    public array $selectedPaymentTerms = [];

    public bool $is_active = true;
    public array $selectedAddOns = [];

    public function mount(Stall $stall): void
    {
        $this->stall = $stall;
        $this->block = $stall->block;
        $this->number = $stall->number;
        $this->description = $stall->description;
        $this->size = $stall->size;
        $this->is_active = $stall->is_active;
        $this->selectedPaymentTerms = $stall->paymentTerms->pluck('ptid')->map(fn ($v) => (int) $v)->toArray();
        $this->selectedAddOns = $stall->addOns->pluck('aoid')->toArray();
    }

    public function save(): void
    {
        $this->block = strtoupper(trim($this->block));
        $this->number = str_pad(trim($this->number), 2, '0', STR_PAD_LEFT);

        $this->validate([
            'block' => [
                'required',
                'regex:/^[A-Z]\d{2}$/',
                Rule::unique('stall', 'block')->where('number', $this->number)->where('market_id', Auth::user()->market_id)->ignore($this->stall->sid, 'sid'),
            ],
            'number' => ['required', 'regex:/^\d{2}$/'],
            'selectedPaymentTerms' => ['array'],
            'selectedPaymentTerms.*' => ['integer', 'exists:payment_terms,ptid'],
        ], [
            'block.regex' => 'Format blok harus 1 huruf + 2 angka (mis. A01).',
            'block.unique' => 'Lapak dengan blok & nomor ini sudah ada.',
            'number.regex' => 'Nomor harus 2 angka (mis. 05).',
        ]);

        // Sinkronisasi aturan bayar: pertahankan sptid untuk term yang tetap dipilih
        // (dealer_stall.sptid merujuk ke sana). Hitung yang ditambah/dihapus.
        $keep = array_values(array_unique(array_map('intval', $this->selectedPaymentTerms)));
        $existing = DB::table('stall_payment_terms')->where('sid', $this->stall->sid)->pluck('sptid', 'ptid');
        $existingPtids = $existing->keys()->map(fn ($p) => (int) $p)->all();
        $removedPtids = array_values(array_diff($existingPtids, $keep));
        $addedPtids = array_values(array_diff($keep, $existingPtids));

        // Guard: jangan hapus aturan bayar yang masih dipakai penyewaan aktif.
        $removedSptids = array_map(fn ($p) => $existing[$p], $removedPtids);
        if ($removedSptids && DealerStall::whereIn('sptid', $removedSptids)->where('deleted', false)->exists()) {
            $this->addError('selectedPaymentTerms', 'Ada aturan bayar yang sedang dipakai penyewaan aktif — akhiri sewanya dulu sebelum menghapus.');

            return;
        }

        DB::transaction(function () use ($removedPtids, $addedPtids) {
            $this->stall->update([
                'block' => $this->block,
                'number' => $this->number,
                'description' => $this->description,
                'size' => $this->size,
                'is_active' => $this->is_active,
                'modified_by' => Auth::id(),
            ]);

            $userId = Auth::id();

            // Sync aturan bayar (inkremental — jangan delete-all agar FK sptid tetap valid).
            if ($removedPtids) {
                DB::table('stall_payment_terms')->where('sid', $this->stall->sid)->whereIn('ptid', $removedPtids)->delete();
            }
            foreach ($addedPtids as $ptid) {
                DB::table('stall_payment_terms')->insert([
                    'market_id' => $this->stall->market_id,
                    'sid' => $this->stall->sid,
                    'ptid' => $ptid,
                    'created_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Sync add-ons (tanpa FK dari tabel lain → aman delete-all + reinsert).
            DB::table('stall_add_ons')->where('sid', $this->stall->sid)->delete();
            foreach ($this->selectedAddOns as $aoid) {
                DB::table('stall_add_ons')->insert([
                    'market_id' => $this->stall->market_id,
                    'sid' => $this->stall->sid,
                    'aoid' => $aoid,
                    'created_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->success('Lapak berhasil diperbarui.');
        $this->redirectBack('stalls.index');
    }

    public function render()
    {
        $freq = ['daily' => 'hari', 'weekly' => 'minggu', 'monthly' => 'bulan', 'annual' => 'tahun'];

        return view('livewire.stalls.edit', [
            'paymentTerms' => PaymentTerm::orderBy('term_name')->get()->map(fn ($pt) => [
                'ptid' => $pt->ptid,
                'name' => $pt->term_name,
                'sub' => 'Rp ' . number_format($pt->price, 0, ',', '.') . ' / '
                    . ($pt->interval_count > 1 ? $pt->interval_count . ' ' : '') . ($freq[$pt->frequency] ?? $pt->frequency)
                    . ' · ' . $pt->dealer_condition,
            ]),
            'addOns' => AddOn::orderBy('add_on')->get()->map(fn ($ao) => [
                'aoid' => $ao->aoid,
                'name' => $ao->add_on,
                'sub' => 'Rp ' . number_format($ao->price, 0, ',', '.') . ' / ' . ($freq[$ao->frequency] ?? $ao->frequency),
            ]),
        ]);
    }
}
