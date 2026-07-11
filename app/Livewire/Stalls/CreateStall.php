<?php

namespace App\Livewire\Stalls;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\AddOn;
use App\Models\PaymentTerm;
use App\Models\Stall;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class CreateStall extends Component
{
    use ReturnsBack;
    use Toast;

    public string $block = '';

    public string $number = '';

    public ?string $description = null;

    public ?string $size = null;

    /** Aturan bayar yang ditawarkan lapak (bisa >1). Disimpan ke pivot stall_payment_terms. */
    public array $selectedPaymentTerms = [];

    public array $selectedAddOns = [];

    public function save(): void
    {
        // Normalisasi: block huruf besar, number 2 digit.
        $this->block = strtoupper(trim($this->block));
        $this->number = str_pad(trim($this->number), 2, '0', STR_PAD_LEFT);

        $this->validate([
            'block' => [
                'required',
                'regex:/^[A-Z]\d{2}$/',
                Rule::unique('stall', 'block')->where('number', $this->number)->where('market_id', Auth::user()->market_id),
            ],
            'number' => ['required', 'regex:/^\d{2}$/'],
            'selectedPaymentTerms' => ['array'],
            'selectedPaymentTerms.*' => ['integer', 'exists:payment_terms,ptid'],
        ], [
            'block.regex' => 'Format blok harus 1 huruf + 2 angka (mis. A01).',
            'block.unique' => 'Lapak dengan blok & nomor ini sudah ada.',
            'number.regex' => 'Nomor harus 2 angka (mis. 05).',
        ]);

        DB::transaction(function () {
            $stall = Stall::create([
                'block' => $this->block,
                'number' => $this->number,
                'description' => $this->description,
                'size' => $this->size,
                'is_active' => true,
                'created_by' => Auth::id(),
            ]);

            $userId = Auth::id();

            foreach (array_unique($this->selectedPaymentTerms) as $ptid) {
                DB::table('stall_payment_terms')->insert([
                    'market_id' => $stall->market_id,
                    'sid' => $stall->sid,
                    'ptid' => $ptid,
                    'created_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($this->selectedAddOns as $aoid) {
                DB::table('stall_add_ons')->insert([
                    'market_id' => $stall->market_id,
                    'sid' => $stall->sid,
                    'aoid' => $aoid,
                    'created_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->success('Lapak berhasil ditambahkan.');
        $this->redirectBack('stalls.index');
    }

    public function render()
    {
        $freq = ['daily' => 'hari', 'weekly' => 'minggu', 'monthly' => 'bulan', 'annual' => 'tahun'];

        return view('livewire.stalls.create', [
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
