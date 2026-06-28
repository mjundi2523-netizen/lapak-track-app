<?php

namespace App\Livewire\Dealers;

use App\Models\Dealer;
use App\Models\DealerStall;
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
class EditDealer extends Component
{
    use Toast;
    use WithFileUploads;

    public Dealer $dealer;

    public string $nik = '';
    public string $name = '';
    public string $birth_date = '';
    public string $address = '';
    public string $phone_number_1 = '';
    public ?string $phone_number_2 = null;
    public ?string $product_type = null;
    public string $status = 'active';
    public bool $is_new = false;
    public ?string $letter_no = null;

    public $scan_id_file = null;
    public ?string $scan_id = null;

    // Tambah penyewaan (hanya muncul saat pedagang tidak punya rental aktif).
    public array $selected_stalls = [];
    public string $rent_start_date = '';
    public ?string $rent_end_date = null;

    public bool $showStallModal = false;
    public string $stallSearch = '';

    public function mount(Dealer $dealer): void
    {
        $this->dealer = $dealer;
        $this->nik = $dealer->nik;
        $this->name = $dealer->name;
        $this->birth_date = $dealer->birth_date?->format('Y-m-d') ?? '';
        $this->address = $dealer->address;
        $this->phone_number_1 = $dealer->phone_number_1;
        $this->phone_number_2 = $dealer->phone_number_2;
        $this->product_type = $dealer->product_type;
        $this->status = $dealer->status;
        $this->is_new = (bool) $dealer->is_new;
        $this->letter_no = $dealer->letter_no;
        $this->scan_id = $dealer->scan_id;
        $this->rent_start_date = Carbon::today()->toDateString();
    }

    // Status "pedagang baru" mengubah set lapak yang valid → reset pilihan.
    public function updatedIsNew(): void
    {
        $this->selected_stalls = [];
    }

    protected function rules(): array
    {
        return [
            'nik' => 'required|string|max:255|unique:dealer,nik,' . $this->dealer->did . ',did',
            'name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'address' => 'required|string|max:255',
            'phone_number_1' => 'required|string|max:255',
            'phone_number_2' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'letter_no' => 'nullable|string|max:100',
            'scan_id_file' => 'nullable|file|max:5120',
            'selected_stalls' => 'array',
            'selected_stalls.*' => 'integer|exists:stall,sid',
            'rent_start_date' => 'nullable|date',
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

    public function save(BillGenerationService $bills): void
    {
        $this->validate();

        $hasActive = $this->dealer->activeRentals()->exists();

        // Task 1: pedagang yang masih menyewa tidak boleh dinonaktifkan.
        if ($this->status === 'inactive' && $hasActive) {
            $this->addError('status', 'Tidak bisa menonaktifkan pedagang yang masih menyewa. Akhiri sewa lapak terlebih dulu.');

            return;
        }

        // Task 4: tambah penyewaan hanya bila pedagang tidak punya rental aktif.
        $addingRental = ! $hasActive && ! empty($this->selected_stalls);
        if ($addingRental) {
            $this->validate(['rent_start_date' => 'required|date']);

            // Tolak lapak yang sudah tersewa (jaga-jaga manipulasi state).
            $occupied = Stall::whereIn('sid', $this->selected_stalls)
                ->whereHas('activeRentals')
                ->pluck('sid')
                ->all();

            if ($occupied) {
                $this->addError('selected_stalls', 'Ada lapak yang sudah tersewa, silakan pilih ulang.');
                $this->selected_stalls = array_values(array_diff($this->selected_stalls, $occupied));

                return;
            }

            // Lapak harus cocok dengan status pedagang (aturan bayar is_new = is_new pedagang).
            $mismatch = Stall::whereIn('sid', $this->selected_stalls)
                ->whereDoesntHave('paymentTerm', fn ($q) => $q->where('is_new', $this->is_new))
                ->pluck('sid')
                ->all();

            if ($mismatch) {
                $this->addError('selected_stalls', 'Ada lapak yang aturan bayarnya tidak sesuai status pedagang (baru/lama).');
                $this->selected_stalls = array_values(array_diff($this->selected_stalls, $mismatch));

                return;
            }
        }

        if ($this->scan_id_file) {
            $this->scan_id = $this->scan_id_file->store('scan-ids', 'public');
        }

        DB::transaction(function () use ($bills, $addingRental) {
            $this->dealer->update([
                'nik' => $this->nik,
                'name' => $this->name,
                'birth_date' => $this->birth_date,
                'address' => $this->address,
                'phone_number_1' => $this->phone_number_1,
                'phone_number_2' => $this->phone_number_2,
                'product_type' => $this->product_type,
                'status' => $this->status,
                'is_new' => $this->is_new,
                'letter_no' => $this->letter_no,
                'scan_id' => $this->scan_id,
                'modified_by' => Auth::id(),
            ]);

            if ($addingRental) {
                foreach ($this->selected_stalls as $sid) {
                    $ds = DealerStall::create([
                        'did' => $this->dealer->did,
                        'sid' => $sid,
                        'rent_start_date' => $this->rent_start_date,
                        'rent_end_date' => $this->rent_end_date,
                        'deleted' => false,
                        'created_by' => Auth::id(),
                    ]);

                    $bills->ensureBillsUpToDate($ds);
                }
            }
        });

        $this->success('Data pedagang berhasil diperbarui.');
        $this->redirect(route('dealers.show', $this->dealer), navigate: true);
    }

    public function render()
    {
        $activeRentals = $this->dealer->activeRentals()->with('stall')->get();
        $hasActiveRental = $activeRentals->isNotEmpty();

        // Daftar lapak untuk modal (hanya relevan saat pedagang tidak punya rental aktif).
        $stalls = $hasActiveRental
            ? collect()
            : Stall::query()
                ->where('is_active', true)
                ->whereHas('paymentTerm', fn ($q) => $q->where('is_new', $this->is_new))
                ->with(['paymentTerm', 'addOns'])
                ->withCount('activeRentals')
                ->when($this->stallSearch !== '', fn ($q) => $q->where('block', 'like', '%' . $this->stallSearch . '%'))
                ->orderBy('block')
                ->get();

        return view('livewire.dealers.edit', [
            'activeRentals' => $activeRentals,
            'hasActiveRental' => $hasActiveRental,
            'stalls' => $stalls,
            'selectedStallDetails' => Stall::whereIn('sid', $this->selected_stalls)
                ->with('paymentTerm')
                ->orderBy('block')
                ->get(),
        ]);
    }
}
