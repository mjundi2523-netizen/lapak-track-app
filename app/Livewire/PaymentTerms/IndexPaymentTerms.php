<?php

namespace App\Livewire\PaymentTerms;

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

    public function render()
    {
        $paymentTerms = PaymentTerm::query()
            ->when($this->search, fn ($q) => $q->where('term_name', 'like', "%{$this->search}%"))
            ->orderBy('term_name')
            ->paginate(10);

        return view('livewire.payment-terms.index', [
            'paymentTerms' => $paymentTerms,
        ]);
    }
}
