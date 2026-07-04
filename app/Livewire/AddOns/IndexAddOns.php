<?php

namespace App\Livewire\AddOns;

use App\Models\AddOn;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexAddOns extends Component
{
    use Toast;
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public function delete(AddOn $addOn): void
    {
        $addOn->update(['modified_by' => Auth::id()]);
        $addOn->delete();
        $this->success('Biaya lain-lain berhasil dihapus.');
    }

    public function render()
    {
        $addOns = AddOn::query()
            ->when($this->search, fn ($q) => $q->where('add_on', 'like', "%{$this->search}%"))
            ->orderBy('add_on')
            ->paginate(10);

        return view('livewire.add-ons.index', [
            'addOns' => $addOns,
        ]);
    }
}
