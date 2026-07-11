<?php

namespace App\Livewire\Dealers;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\Dealer;
use App\Models\DealerStall;
use App\Models\ExternalDealer;
use App\Models\PaymentTerm;
use App\Models\Stall;
use App\Services\BillGenerationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class EditDealer extends Component
{
    use ReturnsBack;
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
    public string $dealer_condition = 'regular';
    public ?string $letter_no = null;

    public $scan_id_file = null;
    public ?string $scan_id = null;

    // Tambah penyewaan (hanya muncul saat pedagang tidak punya rental aktif).
    public array $selected_stalls = [];
    /** Aturan bayar terpilih per lapak (sid => sptid) untuk penyewaan baru. */
    public array $stall_term_choice = [];
    public ?int $selected_ptid = null;          // aturan bayar (pedagang eksternal)
    public string $external_start_date = '';    // mulai langganan eksternal baru
    public string $rent_start_date = '';
    public ?string $rent_end_date = null;

    public bool $showStallModal = false;
    public string $stallSearch = '';

    /** Modal konfirmasi simpan — hanya saat ada penyewaan/langganan BARU (memicu tagihan otomatis). */
    public bool $showSaveConfirm = false;

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
        $this->dealer_condition = $dealer->dealer_condition;
        $this->letter_no = $dealer->letter_no;
        $this->scan_id = $dealer->scan_id;
        $this->rent_start_date = Carbon::today()->toDateString();
        $this->external_start_date = Carbon::today()->toDateString();
    }

    // Kondisi pedagang mengubah set lapak yang valid → reset pilihan.
    public function updatedDealerCondition($value): void
    {
        // Tidak boleh ubah Jenis Pedagang selagi masih ada kontrak aktif (sewa lapak / langganan
        // eksternal). Kembalikan pilihan & arahkan ke detail agar diakhiri dulu.
        if ($this->dealer->activeRentals()->exists() || $this->dealer->activeExternal()->exists()) {
            $this->dealer_condition = $this->dealer->dealer_condition;
            $this->warning('Akhiri dulu semua sewa/kontrak aktif pedagang ini sebelum mengubah Jenis Pedagang.');
            $this->redirectRoute('dealers.show', $this->dealer, navigate: true);

            return;
        }

        // Fitur "Pedagang eksternal" = premium.
        if ($value === 'external' && ! auth()->user()->isPremium()) {
            $this->dealer_condition = 'regular';
            $this->dispatch('premium-required');

            return;
        }

        $this->selected_stalls = [];
        $this->stall_term_choice = [];
        $this->selected_ptid = null;
    }

    protected function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'max:255', Rule::unique('dealer', 'nik')->where('market_id', Auth::user()->market_id)->ignore($this->dealer->did, 'did')],
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
            'selected_ptid' => 'nullable|integer|exists:payment_terms,ptid',
            'external_start_date' => 'nullable|date',
        ];
    }

    public function toggleStall(int $sid): void
    {
        if (in_array($sid, $this->selected_stalls, true)) {
            $this->selected_stalls = array_values(array_diff($this->selected_stalls, [$sid]));
            unset($this->stall_term_choice[$sid]);
        } else {
            $this->selected_stalls[] = $sid;
            // Auto-pilih bila lapak hanya punya 1 aturan bayar yang cocok kondisi pedagang.
            $opts = $this->stallTermOptions($sid);
            $this->stall_term_choice[$sid] = count($opts) === 1 ? (int) $opts[0]['sptid'] : null;
        }
    }

    /** Set aturan bayar terpilih untuk sebuah lapak (sekaligus menandai lapak terpilih). */
    public function setStallTerm(int $sid, int $sptid): void
    {
        if (! in_array($sid, $this->selected_stalls, true)) {
            $this->selected_stalls[] = $sid;
        }
        $this->stall_term_choice[$sid] = $sptid;
    }

    /**
     * Opsi aturan bayar sebuah lapak yang cocok kondisi pedagang.
     * Return: [['sptid','ptid','term_name','price','frequency','interval_count'], ...].
     * Raw query → filter market_id manual (global scope tidak berlaku).
     */
    protected function stallTermOptions(int $sid): array
    {
        return DB::table('stall_payment_terms as spt')
            ->join('payment_terms as pt', 'pt.ptid', '=', 'spt.ptid')
            ->where('spt.sid', $sid)
            ->where('pt.dealer_condition', $this->dealer_condition)
            ->when(Auth::user()?->market_id, fn ($q, $m) => $q->where('spt.market_id', $m))
            ->orderBy('pt.price')
            ->get(['spt.sptid', 'pt.ptid', 'pt.term_name', 'pt.price', 'pt.frequency', 'pt.interval_count'])
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Tahap 1: validasi penuh. Bila perubahan memicu tagihan otomatis
     * (penyewaan/langganan baru) → minta konfirmasi dulu; selain itu langsung simpan.
     */
    public function save(BillGenerationService $bills): void
    {
        $ctx = $this->validateForSave();
        if ($ctx === null) {
            return;
        }

        if ($ctx['addingRental'] || $ctx['addingExternal']) {
            $this->showSaveConfirm = true;

            return;
        }

        $this->persist($bills, $ctx);
    }

    /** Tahap 2: user mengonfirmasi pembuatan penyewaan/langganan baru + tagihannya. */
    public function confirmSave(BillGenerationService $bills): void
    {
        $this->showSaveConfirm = false;

        // Validasi ulang: kondisi bisa berubah selagi modal terbuka (mis. lapak keburu tersewa).
        $ctx = $this->validateForSave();
        if ($ctx === null) {
            return;
        }

        $this->persist($bills, $ctx);
    }

    /** Seluruh validasi + guard. Return null bila gagal; selain itu konteks simpan. */
    protected function validateForSave(): ?array
    {
        // Normalisasi tanggal opsional kosong ('') → null (hindari insert '' ke kolom DATE → 500).
        $this->rent_end_date = $this->rent_end_date ?: null;

        $this->validate();

        $hasActiveRental = $this->dealer->activeRentals()->exists();
        $hasActiveExternal = $this->dealer->activeExternal()->exists();

        // Pertahanan berlapis: dealer_condition tidak boleh berubah selagi ada kontrak aktif,
        // walau state sempat dimanipulasi (updatedDealerCondition sudah menolak di sisi UI).
        if ($this->dealer_condition !== $this->dealer->getOriginal('dealer_condition')
            && ($hasActiveRental || $hasActiveExternal)) {
            $this->addError('dealer_condition', 'Tidak bisa mengubah Jenis Pedagang selagi ada kontrak aktif. Akhiri dulu di halaman detail.');

            return null;
        }

        // Tidak bisa nonaktifkan kalau masih punya sewa/langganan aktif.
        if ($this->status === 'inactive' && ($hasActiveRental || $hasActiveExternal)) {
            $this->addError('status', 'Tidak bisa menonaktifkan pedagang yang masih menyewa / berlangganan aktif. Akhiri dulu.');

            return null;
        }

        // Tambah penyewaan (regular/new). Boleh menambah lapak baru walau sudah punya rental aktif
        // (rental lama tetap readonly; yang ditambah di sini jadi rental baru + tagihannya).
        $addingRental = $this->dealer_condition !== 'external' && ! empty($this->selected_stalls);
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

                return null;
            }

            // Tiap lapak wajib punya aturan bayar terpilih yang valid: milik lapak & cocok kondisi.
            foreach ($this->selected_stalls as $sid) {
                $chosen = $this->stall_term_choice[$sid] ?? null;
                $validSptids = array_map(fn ($o) => (int) $o['sptid'], $this->stallTermOptions($sid));

                if (! $chosen || ! in_array((int) $chosen, $validSptids, true)) {
                    $this->addError('selected_stalls', 'Setiap lapak harus dipilih satu aturan bayar yang sesuai kondisi pedagang.');

                    return null;
                }
            }
        }

        // Tambah langganan eksternal hanya bila belum punya yang aktif.
        $addingExternal = $this->dealer_condition === 'external' && ! $hasActiveExternal && $this->selected_ptid;

        // Guard premium: cegah penambahan langganan eksternal baru oleh akun non-premium.
        if ($addingExternal && ! auth()->user()->isPremium()) {
            $this->dispatch('premium-required');

            return null;
        }

        if ($addingExternal) {
            $this->validate([
                'selected_ptid' => 'required|integer|exists:payment_terms,ptid',
                'external_start_date' => 'required|date',
            ]);
        }

        return ['addingRental' => $addingRental, 'addingExternal' => $addingExternal];
    }

    /** Simpan perubahan + buat penyewaan/langganan baru (bila ada) beserta tagihannya. */
    protected function persist(BillGenerationService $bills, array $ctx): void
    {
        ['addingRental' => $addingRental, 'addingExternal' => $addingExternal] = $ctx;

        if ($this->scan_id_file) {
            $this->scan_id = \App\Support\Upload::storeImageAsWebp($this->scan_id_file, 'scan-ids');
        }

        DB::transaction(function () use ($bills, $addingRental, $addingExternal) {
            $this->dealer->update([
                'nik' => $this->nik,
                'name' => $this->name,
                'birth_date' => $this->birth_date,
                'address' => $this->address,
                'phone_number_1' => $this->phone_number_1,
                'phone_number_2' => $this->phone_number_2,
                'product_type' => $this->product_type,
                'status' => $this->status,
                'dealer_condition' => $this->dealer_condition,
                'letter_no' => $this->letter_no,
                'scan_id' => $this->scan_id,
                'modified_by' => Auth::id(),
            ]);

            if ($addingRental) {
                foreach ($this->selected_stalls as $sid) {
                    $ds = DealerStall::create([
                        'did' => $this->dealer->did,
                        'sid' => $sid,
                        'sptid' => $this->stall_term_choice[$sid] ?? null,
                        'rent_start_date' => $this->rent_start_date,
                        'rent_end_date' => $this->rent_end_date,
                        'deleted' => false,
                        'created_by' => Auth::id(),
                    ]);

                    $bills->ensureBillsUpToDate($ds);
                }
            }

            if ($addingExternal) {
                $ed = ExternalDealer::create([
                    'did' => $this->dealer->did,
                    'ptid' => $this->selected_ptid,
                    'start_date' => $this->external_start_date,
                    'deleted' => false,
                    'created_by' => Auth::id(),
                ]);

                $bills->ensureExternalBillsUpToDate($ed);
            }
        });

        $this->success('Data pedagang berhasil diperbarui.');
        $this->redirectBack('dealers.show', $this->dealer);
    }

    public function render()
    {
        $activeRentals = $this->dealer->activeRentals()->with('stall')->get();
        $hasActiveRental = $activeRentals->isNotEmpty();

        $activeExternal = $this->dealer->activeExternal()->with('paymentTerm')->get();
        $hasActiveExternal = $activeExternal->isNotEmpty();

        // Daftar lapak untuk modal (untuk pedagang non-eksternal; boleh menambah lapak baru
        // walau sudah punya rental aktif — lapak yang sudah tersewa otomatis tidak bisa dipilih).
        $cond = $this->dealer_condition;

        $stalls = $this->dealer_condition === 'external'
            ? collect()
            : Stall::query()
                ->where('is_active', true)
                ->whereHas('paymentTerms', fn ($q) => $q->where('dealer_condition', $cond))
                ->with([
                    'paymentTerms' => fn ($q) => $q->where('dealer_condition', $cond),
                    'addOns',
                ])
                ->withCount('activeRentals')
                ->when($this->stallSearch !== '', fn ($q) => $q->where('block', 'like', '%' . $this->stallSearch . '%'))
                ->orderBy('block')
                ->get();

        return view('livewire.dealers.edit', [
            'activeRentals' => $activeRentals,
            'hasActiveRental' => $hasActiveRental,
            'activeExternal' => $activeExternal,
            'hasActiveExternal' => $hasActiveExternal,
            'stalls' => $stalls,
            'selectedStallDetails' => Stall::whereIn('sid', $this->selected_stalls)
                ->with(['paymentTerms' => fn ($q) => $q->where('dealer_condition', $cond)])
                ->orderBy('block')
                ->get(),
            'paymentTerms' => $this->dealer_condition === 'external'
                ? PaymentTerm::where('dealer_condition', $cond)->orderBy('term_name')->get()
                : collect(),
        ]);
    }
}
