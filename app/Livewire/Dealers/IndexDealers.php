<?php

namespace App\Livewire\Dealers;

use App\Models\Dealer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexDealers extends Component
{
    use Toast;
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public function render()
    {
        $dealers = Dealer::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('nik', 'like', "%{$this->search}%")
            )
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.dealers.index', [
            'dealers' => $dealers,
        ]);
    }
}
