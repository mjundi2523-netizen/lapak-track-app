<?php

namespace App\Livewire\Dealers;

use App\Models\Dealer;
use App\Models\DealerStall;
use App\Models\ExternalDealer;
use App\Models\PaymentTerm;
use App\Models\Stall;
use App\Services\BillGenerationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class CreateDealer extends Component
{
    use Toast;
    use WithFileUploads;

    public string $nik = '';
    public string $name = '';
    public string $birth_date = '';
    public string $address = '';
    public string $phone_number_1 = '';
    public ?string $phone_number_2 = null;
    public ?string $product_type = null;
    public ?string $status = 'active';
    public bool $cond_new = false;
    public bool $cond_external = false;
    public ?string $letter_no = null;

    public $scan_id_file = null;
    public ?string $scan_id = null;

    public array $selected_stalls = [];
    public ?int $selected_ptid = null;          // aturan bayar (pedagang eksternal)
    public string $external_start_date = '';    // mulai langganan eksternal
    public string $rent_start_date = '';
    public ?string $rent_end_date = null;

    public bool $showStallModal = false;
    public string $stallSearch = '';

    public function mount(): void
    {
        $this->external_start_date = Carbon::today()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'nik' => 'required|string|max:255|unique:dealer,nik',
            'name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'address' => 'required|string|max:255',
            'phone_number_1' => 'required|string|max:255',
            'phone_number_2' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'letter_no' => 'nullable|string|max:100',
            'scan_id_file' => 'nullable|file|max:5120',
            'selected_stalls' => 'nullable|array',
            'selected_stalls.*' => 'integer|exists:stall,sid',
            'rent_start_date' => 'nullable|date',
            'rent_end_date' => 'nullable|date|after_or_equal:rent_start_date',
            'selected_ptid' => 'nullable|integer|exists:payment_terms,ptid',
            'external_start_date' => 'nullable|date',
        ];
    }

    // Kondisi pedagang mengubah set lapak yang valid → reset pilihan + jaga mutually-exclusive.
    public function updatedCondNew($value): void
    {
        if ($value) {
            $this->cond_external = false;
        }
        $this->selected_stalls = [];
        $this->selected_ptid = null;
    }

    public function updatedCondExternal($value): void
    {
        // Fitur "Pedagang eksternal" = premium.
        if ($value && ! auth()->user()->isPremium()) {
            $this->cond_external = false;
            $this->dispatch('premium-required');

            return;
        }

        if ($value) {
            $this->cond_new = false;
        }
        $this->selected_stalls = [];
        $this->selected_ptid = null;
    }

    protected function dealerCondition(): string
    {
        return $this->cond_external ? 'external' : ($this->cond_new ? 'new' : 'regular');
    }

    public function toggleStall(int $sid): void
    {
        if (in_array($sid, $this->selected_stalls, true)) {
            $this->selected_stalls = array_values(array_diff($this->selected_stalls, [$sid]));
        } else {
            $this->selected_stalls[] = $sid;
        }
    }

    public function save(BillGenerationService $billService): void
    {
        // Guard premium: cegah pembuatan pedagang eksternal oleh akun non-premium.
        if ($this->cond_external && ! auth()->user()->isPremium()) {
            $this->cond_external = false;
            $this->dispatch('premium-required');

            return;
        }

        $this->validate();

        // Tanggal mulai sewa wajib hanya bila ada lapak yang dipilih.
        if (! empty($this->selected_stalls)) {
            $this->validate(['rent_start_date' => 'required|date']);
        }

        // Pedagang eksternal: wajib pilih aturan bayar + tanggal mulai langganan.
        if ($this->cond_external) {
            $this->validate([
                'selected_ptid' => 'required|integer|exists:payment_terms,ptid',
                'external_start_date' => 'required|date',
            ]);
        }

        // Tolak lapak yang sudah tersewa (jaga-jaga jika dipilih lewat manipulasi state).
        $occupied = Stall::whereIn('sid', $this->selected_stalls)
            ->whereHas('activeRentals')
            ->pluck('sid')
            ->all();

        if ($occupied) {
            $this->addError('selected_stalls', 'Ada lapak yang sudah tersewa, silakan pilih ulang.');
            $this->selected_stalls = array_values(array_diff($this->selected_stalls, $occupied));

            return;
        }

        // Lapak harus cocok kondisi pedagang: payment_terms.dealer_condition = dealer.dealer_condition.
        $mismatch = Stall::whereIn('sid', $this->selected_stalls)
            ->whereDoesntHave('paymentTerm', fn ($q) => $q->where('dealer_condition', $this->dealerCondition()))
            ->pluck('sid')
            ->all();

        if ($mismatch) {
            $this->addError('selected_stalls', 'Ada lapak yang aturan bayarnya tidak sesuai kondisi pedagang.');
            $this->selected_stalls = array_values(array_diff($this->selected_stalls, $mismatch));

            return;
        }

        if ($this->scan_id_file) {
            $this->scan_id = $this->scan_id_file->store('scan-ids', 'public');
        }

        DB::transaction(function () use ($billService) {
            $dealer = Dealer::create([
                'nik' => $this->nik,
                'name' => $this->name,
                'birth_date' => $this->birth_date,
                'address' => $this->address,
                'phone_number_1' => $this->phone_number_1,
                'phone_number_2' => $this->phone_number_2,
                'product_type' => $this->product_type,
                'status' => $this->status ?? 'active',
                'dealer_condition' => $this->dealerCondition(),
                'letter_no' => $this->letter_no,
                'scan_id' => $this->scan_id,
                'created_by' => Auth::id(),
            ]);

            if ($this->cond_external) {
                // Langganan eksternal (relasi langsung ke aturan bayar), lalu generate tagihannya.
                $ed = ExternalDealer::create([
                    'did' => $dealer->did,
                    'ptid' => $this->selected_ptid,
                    'start_date' => $this->external_start_date,
                    'deleted' => false,
                    'created_by' => Auth::id(),
                ]);

                $billService->ensureExternalBillsUpToDate($ed);
            } else {
                // Buat satu rental (dealer_stall) per lapak yang dipilih, lalu generate tagihannya.
                foreach ($this->selected_stalls as $sid) {
                    $ds = DealerStall::create([
                        'did' => $dealer->did,
                        'sid' => $sid,
                        'rent_start_date' => $this->rent_start_date,
                        'rent_end_date' => $this->rent_end_date,
                        'deleted' => false,
                        'created_by' => Auth::id(),
                    ]);

                    $billService->ensureBillsUpToDate($ds);
                }
            }
        });

        $this->success('Pedagang berhasil ditambahkan.');
        $this->redirect(route('dealers.index'), navigate: true);
    }

    public function render()
    {
        // Lapak aktif yang aturan bayarnya sesuai kondisi pedagang.
        $stalls = Stall::query()
            ->where('is_active', true)
            ->whereHas('paymentTerm', fn ($q) => $q->where('dealer_condition', $this->dealerCondition()))
            ->with(['paymentTerm', 'addOns'])
            ->withCount('activeRentals')
            ->when($this->stallSearch !== '', fn ($q) => $q->where('block', 'like', '%' . $this->stallSearch . '%'))
            ->orderBy('block')
            ->get();

        return view('livewire.dealers.create', [
            'stalls' => $stalls,
            // Detail lapak terpilih untuk ringkasan di form.
            'selectedStallDetails' => Stall::whereIn('sid', $this->selected_stalls)
                ->with('paymentTerm')
                ->orderBy('block')
                ->get(),
            // Aturan bayar untuk pedagang eksternal (tanpa lapak).
            'paymentTerms' => $this->cond_external
                ? PaymentTerm::where('dealer_condition', $this->dealerCondition())->orderBy('term_name')->get()
                : collect(),
        ]);
    }
}
