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
class EditStall extends Component
{
    use ReturnsBack;
    use Toast;

    public Stall $stall;

    public string $block = '';

    public string $number = '';

    public ?string $description = null;

    public ?string $size = null;

    public ?int $ptid = null;

    public bool $is_active = true;
    public array $selectedAddOns = [];

    public function mount(Stall $stall): void
    {
        $this->stall = $stall;
        $this->block = $stall->block;
        $this->number = $stall->number;
        $this->description = $stall->description;
        $this->size = $stall->size;
        $this->ptid = $stall->ptid;
        $this->is_active = $stall->is_active;
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
            'ptid' => ['nullable', 'exists:payment_terms,ptid'],
        ], [
            'block.regex' => 'Format blok harus 1 huruf + 2 angka (mis. A01).',
            'block.unique' => 'Lapak dengan blok & nomor ini sudah ada.',
            'number.regex' => 'Nomor harus 2 angka (mis. 05).',
        ]);

        DB::transaction(function () {
            $this->stall->update([
                'block' => $this->block,
                'number' => $this->number,
                'description' => $this->description,
                'size' => $this->size,
                'ptid' => $this->ptid,
                'is_active' => $this->is_active,
                'modified_by' => Auth::id(),
            ]);

            // Sync add-ons
            DB::table('stall_add_ons')->where('sid', $this->stall->sid)->delete();

            if (! empty($this->selectedAddOns)) {
                $userId = Auth::id();
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
            }
        });

        $this->success('Lapak berhasil diperbarui.');
        $this->redirectBack('stalls.index');
    }

    public function render()
    {
        return view('livewire.stalls.edit', [
            'paymentTerms' => PaymentTerm::orderBy('term_name')->get(),
            'addOns' => AddOn::orderBy('add_on')->get(),
        ]);
    }
}
