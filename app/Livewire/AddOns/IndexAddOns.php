<?php

namespace App\Livewire\AddOns;

use App\Livewire\Concerns\Sortable;
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
    use Sortable;
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

    /** Kolom sortable (klik header). */
    protected function sortColumns(): array
    {
        return [
            'add_on' => 'add_on',
            'frequency' => 'frequency',
            'price' => 'price',
        ];
    }
    public function render()
    {
        $addOns = AddOn::query()
            ->when($this->search, fn ($q) => $q->where('add_on', 'like', "%{$this->search}%"));

        $this->applySort($addOns, fn ($q) => $q->orderBy('add_on'));

        $addOns = $addOns->paginate(10);

        return view('livewire.add-ons.index', [
            'addOns' => $addOns,
        ]);
    }
}
