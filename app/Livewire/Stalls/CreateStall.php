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

    public ?int $ptid = null;

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
                Rule::unique('stall', 'block')->where('number', $this->number),
            ],
            'number' => ['required', 'regex:/^\d{2}$/'],
            'ptid' => ['nullable', 'exists:payment_terms,ptid'],
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
                'ptid' => $this->ptid,
                'is_active' => true,
                'created_by' => Auth::id(),
            ]);

            if (! empty($this->selectedAddOns)) {
                $userId = Auth::id();
                foreach ($this->selectedAddOns as $aoid) {
                    DB::table('stall_add_ons')->insert([
                        'sid' => $stall->sid,
                        'aoid' => $aoid,
                        'created_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $this->success('Lapak berhasil ditambahkan.');
        $this->redirectBack('stalls.index');
    }

    public function render()
    {
        return view('livewire.stalls.create', [
            'paymentTerms' => PaymentTerm::orderBy('term_name')->get(),
            'addOns' => AddOn::orderBy('add_on')->get(),
        ]);
    }
}
