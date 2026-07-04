<?php

namespace App\Livewire\Dealers;

use App\Livewire\Concerns\Sortable;
use App\Models\Dealer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexDealers extends Component
{
    use Sortable;
    use Toast;
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    /** Kolom sortable (klik header). */
    protected function sortColumns(): array
    {
        return [
            'nik' => 'nik',
            'name' => 'name',
            'dealer_condition' => 'dealer_condition',
            'phone' => 'phone_number_1',
            'status' => 'status',
        ];
    }
    public function render()
    {
        $dealers = Dealer::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('nik', 'like', "%{$this->search}%")
            );

        $this->applySort($dealers, fn ($q) => $q->orderBy('name'));

        $dealers = $dealers->paginate(10);

        return view('livewire.dealers.index', [
            'dealers' => $dealers,
        ]);
    }
}
