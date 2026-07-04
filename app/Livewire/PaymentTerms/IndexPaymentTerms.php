<?php

namespace App\Livewire\PaymentTerms;

use App\Livewire\Concerns\Sortable;
use App\Models\PaymentTerm;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexPaymentTerms extends Component
{
    use Sortable;
    use Toast;
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public function delete(PaymentTerm $paymentTerm): void
    {
        $paymentTerm->update(['modified_by' => Auth::id()]);
        $paymentTerm->delete();
        $this->success('Aturan bayar berhasil dihapus.');
    }

    /** Kolom sortable (klik header). */
    protected function sortColumns(): array
    {
        return [
            'term_name' => 'term_name',
            'dealer_condition' => 'dealer_condition',
            'frequency' => 'frequency',
            'price' => 'price',
        ];
    }
    public function render()
    {
        $paymentTerms = PaymentTerm::query()
            ->when($this->search, fn ($q) => $q->where('term_name', 'like', "%{$this->search}%"));

        $this->applySort($paymentTerms, fn ($q) => $q->orderBy('term_name'));

        $paymentTerms = $paymentTerms->paginate(10);

        return view('livewire.payment-terms.index', [
            'paymentTerms' => $paymentTerms,
        ]);
    }
}
