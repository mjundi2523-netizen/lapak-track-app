<?php

namespace App\Livewire\Dealers;

use App\Models\Dealer;
use App\Models\DealerStall;
use App\Models\Stall;
use App\Services\BillGenerationService;
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

    public $scan_id_file = null;
    public ?string $scan_id = null;

    public array $selected_stalls = [];
    public string $rent_start_date = '';
    public ?string $rent_end_date = null;

    public bool $showStallModal = false;
    public string $stallSearch = '';

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
            'scan_id_file' => 'nullable|file|max:5120',
            'selected_stalls' => 'required|array|min:1',
            'selected_stalls.*' => 'integer|exists:stall,sid',
            'rent_start_date' => 'required|date',
            'rent_end_date' => 'nullable|date|after_or_equal:rent_start_date',
        ];
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
        $this->validate();

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
                'scan_id' => $this->scan_id,
                'created_by' => Auth::id(),
            ]);

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
        });

        $this->success('Pedagang berhasil ditambahkan.');
        $this->redirect(route('dealers.index'), navigate: true);
    }

    public function render()
    {
        // Semua lapak aktif (tersewa & kosong) untuk modal pemilihan.
        $stalls = Stall::query()
            ->where('is_active', true)
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
        ]);
    }
}
