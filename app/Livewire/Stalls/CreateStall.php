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
class CreateStall extends Component
{
    use Toast;

    #[Validate('required|string|max:255|unique:stall,block')]
    public string $block = '';

    public ?string $description = null;

    #[Validate('nullable|exists:payment_terms,ptid')]
    public ?int $ptid = null;

    public array $selectedAddOns = [];

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            $stall = Stall::create([
                'block' => $this->block,
                'description' => $this->description,
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
        $this->redirect(route('stalls.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.stalls.create', [
            'paymentTerms' => PaymentTerm::orderBy('term_name')->get(),
            'addOns' => AddOn::orderBy('add_on')->get(),
        ]);
    }
}
