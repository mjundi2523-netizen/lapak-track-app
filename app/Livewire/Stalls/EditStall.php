<?php

namespace App\Livewire\Stalls;

use App\Models\AddOn;
use App\Models\PaymentTerm;
use App\Models\Stall;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class EditStall extends Component
{
    use Toast;

    public Stall $stall;

    #[Validate('required|string|max:255')]
    public string $block = '';

    public ?string $description = null;

    #[Validate('nullable|exists:payment_terms,ptid')]
    public ?int $ptid = null;

    public bool $is_active = true;
    public array $selectedAddOns = [];

    public function mount(Stall $stall): void
    {
        $this->stall = $stall;
        $this->block = $stall->block;
        $this->description = $stall->description;
        $this->ptid = $stall->ptid;
        $this->is_active = $stall->is_active;
        $this->selectedAddOns = $stall->addOns->pluck('aoid')->toArray();
    }

    public function save(): void
    {
        $this->validate([
            'block' => 'required|string|max:255|unique:stall,block,' . $this->stall->sid . ',sid',
            'ptid' => 'nullable|exists:payment_terms,ptid',
        ]);

        DB::transaction(function () {
            $this->stall->update([
                'block' => $this->block,
                'description' => $this->description,
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
        $this->redirect(route('stalls.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.stalls.edit', [
            'paymentTerms' => PaymentTerm::orderBy('term_name')->get(),
            'addOns' => AddOn::orderBy('add_on')->get(),
        ]);
    }
}
