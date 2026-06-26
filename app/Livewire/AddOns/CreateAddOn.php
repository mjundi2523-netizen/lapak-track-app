<?php

namespace App\Livewire\AddOns;

use App\Models\AddOn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class CreateAddOn extends Component
{
    use Toast;

    public string $add_on = '';

    public int $price = 0;

    public string $frequency = 'monthly';

    public bool $is_rent_date = true;

    public ?string $start_date = null;

    protected function rules(): array
    {
        return [
            'add_on' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'frequency' => 'required|in:daily,weekly,monthly,annual',
            'is_rent_date' => 'boolean',
            // start_date wajib hanya saat jadwal lepas (tidak ikut tanggal sewa).
            'start_date' => 'exclude_if:is_rent_date,true|required|date',
        ];
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            AddOn::create([
                'add_on' => $this->add_on,
                'price' => $this->price,
                'frequency' => $this->frequency,
                'is_rent_date' => $this->is_rent_date,
                'start_date' => $this->is_rent_date ? null : $this->start_date,
                'created_by' => Auth::id(),
            ]);
        });

        $this->success('Biaya lain-lain berhasil ditambahkan.');
        $this->redirect(route('add-ons.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.add-ons.create');
    }
}
