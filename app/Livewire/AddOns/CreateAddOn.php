<?php

namespace App\Livewire\AddOns;

use App\Models\AddOn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class CreateAddOn extends Component
{
    use Toast;

    #[Validate('required|string|max:255')]
    public string $add_on = '';

    #[Validate('required|integer|min:0')]
    public int $price = 0;

    #[Validate('required|in:daily,weekly,monthly,annual')]
    public string $frequency = 'monthly';

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            AddOn::create([
                'add_on' => $this->add_on,
                'price' => $this->price,
                'frequency' => $this->frequency,
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
