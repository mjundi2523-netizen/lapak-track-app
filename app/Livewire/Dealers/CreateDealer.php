<?php

namespace App\Livewire\Dealers;

use App\Models\Dealer;
use App\Models\DealerStall;
use App\Models\Stall;
use App\Services\BillGenerationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class CreateDealer extends Component
{
    use Toast;
    use WithFileUploads;

    #[Validate('required|string|max:255|unique:dealer,nik')]
    public string $nik = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|date')]
    public string $birth_date = '';

    #[Validate('required|string|max:255')]
    public string $address = '';

    #[Validate('required|string|max:255')]
    public string $phone_number_1 = '';

    public ?string $phone_number_2 = null;
    public ?string $product_type = null;
    public ?string $status = 'active';

    public $scan_id_file = null;
    public ?string $scan_id = null;

    #[Validate('required|integer|exists:stall,sid')]
    public ?int $selected_stall = null;

    #[Validate('required|date')]
    public string $rent_start_date = '';

    public ?string $rent_end_date = null;

    public function save(BillGenerationService $billService): void
    {
        $this->validate();

        if ($this->scan_id_file) {
            $this->scan_id = $this->scan_id_file->store('scan-ids', 'public');
        }

        DB::transaction(function () use ($billService) {
            $dealer = Dealer::create([
                'nik' => $this->nik,
                'name' => $this->name,
                'birth_date' => $this->birth_date,
                'address' => $this->address,
                'phone_number_1' => $this->phone_number_1,
                'phone_number_2' => $this->phone_number_2,
                'product_type' => $this->product_type,
                'status' => $this->status ?? 'active',
                'scan_id' => $this->scan_id,
                'created_by' => Auth::id(),
            ]);

            $ds = DealerStall::create([
                'did' => $dealer->did,
                'sid' => $this->selected_stall,
                'rent_start_date' => $this->rent_start_date,
                'rent_end_date' => $this->rent_end_date,
                'deleted' => false,
                'created_by' => Auth::id(),
            ]);

            // Generate bills for this dealer-stall
            $billService->generateBillsForDealerStall($ds);
        });

        $this->success('Pedagang berhasil ditambahkan.');
        $this->redirect(route('dealers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.dealers.create', [
            'stalls' => Stall::where('is_active', true)
                ->orderBy('block')
                ->get(),
        ]);
    }
}
