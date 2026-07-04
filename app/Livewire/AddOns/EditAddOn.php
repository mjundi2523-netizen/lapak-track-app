<?php

namespace App\Livewire\AddOns;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\AddOn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class EditAddOn extends Component
{
    use ReturnsBack;
    use Toast;

    public AddOn $addOn;

    public string $add_on = '';

    public int $price = 0;

    public string $frequency = '';

    public bool $is_rent_date = true;

    public ?string $start_date = null;

    public function mount(AddOn $addOn): void
    {
        $this->addOn = $addOn;
        $this->add_on = $addOn->add_on;
        $this->price = $addOn->price;
        $this->frequency = $addOn->frequency;
        $this->is_rent_date = (bool) $addOn->is_rent_date;
        $this->start_date = $addOn->start_date?->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'add_on' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'frequency' => 'required|in:daily,weekly,monthly,annual',
            'is_rent_date' => 'boolean',
            'start_date' => 'exclude_if:is_rent_date,true|required|date',
        ];
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            $this->addOn->update([
                'add_on' => $this->add_on,
                'price' => $this->price,
                'frequency' => $this->frequency,
                'is_rent_date' => $this->is_rent_date,
                'start_date' => $this->is_rent_date ? null : $this->start_date,
                'modified_by' => Auth::id(),
            ]);
        });

        $this->success('Biaya lain-lain berhasil diperbarui.');
        $this->redirectBack('add-ons.index');
    }

    public function render()
    {
        return view('livewire.add-ons.edit');
    }
}
