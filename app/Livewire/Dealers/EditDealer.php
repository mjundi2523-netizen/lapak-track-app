<?php

namespace App\Livewire\Dealers;

use App\Models\Dealer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class EditDealer extends Component
{
    use Toast;
    use WithFileUploads;

    public Dealer $dealer;

    #[Validate('required|string|max:255')]
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
    public string $status = 'active';

    public $scan_id_file = null;
    public ?string $scan_id = null;

    public function mount(Dealer $dealer): void
    {
        $this->dealer = $dealer;
        $this->nik = $dealer->nik;
        $this->name = $dealer->name;
        $this->birth_date = $dealer->birth_date?->format('Y-m-d') ?? '';
        $this->address = $dealer->address;
        $this->phone_number_1 = $dealer->phone_number_1;
        $this->phone_number_2 = $dealer->phone_number_2;
        $this->product_type = $dealer->product_type;
        $this->status = $dealer->status;
        $this->scan_id = $dealer->scan_id;
    }

    public function save(): void
    {
        $this->validate([
            'nik' => 'required|string|max:255|unique:dealer,nik,' . $this->dealer->did . ',did',
        ]);

        if ($this->scan_id_file) {
            $this->scan_id = $this->scan_id_file->store('scan-ids', 'public');
        }

        DB::transaction(function () {
            $this->dealer->update([
                'nik' => $this->nik,
                'name' => $this->name,
                'birth_date' => $this->birth_date,
                'address' => $this->address,
                'phone_number_1' => $this->phone_number_1,
                'phone_number_2' => $this->phone_number_2,
                'product_type' => $this->product_type,
                'status' => $this->status,
                'scan_id' => $this->scan_id,
                'modified_by' => Auth::id(),
            ]);
        });

        $this->success('Data pedagang berhasil diperbarui.');
        $this->redirect(route('dealers.show', $this->dealer), navigate: true);
    }

    public function render()
    {
        return view('livewire.dealers.edit');
    }
}
