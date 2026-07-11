<?php

namespace App\Livewire\Dealers;

use App\Livewire\Concerns\ReturnsBack;
use App\Models\Dealer;
use App\Models\DealerStall;
use App\Models\ExternalDealer;
use App\Models\PaymentTerm;
use App\Models\Stall;
use App\Imports\DealersImport;
use App\Services\BillGenerationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Mary\Traits\Toast;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

#[Layout('layouts.app')]
class CreateDealer extends Component
{
    use ReturnsBack;
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
    /** Aturan bayar terpilih per lapak (sid => sptid) untuk pedagang lapak. */
    public array $stall_term_choice = [];
    public ?int $selected_ptid = null;          // aturan bayar (pedagang eksternal)
    public string $external_start_date = '';    // mulai langganan eksternal
    public string $rent_start_date = '';
    public ?string $rent_end_date = null;

    public bool $showStallModal = false;
    public string $stallSearch = '';

    /** Modal konfirmasi simpan (menjelaskan tagihan yang akan dibuat otomatis). */
    public bool $showSaveConfirm = false;

    // --- Impor massal dari Excel (kebutuhan migrasi) ---
    public $import_file = null;
    /** Daftar error per-baris; impor batal total bila tidak kosong. */
    public array $importErrors = [];

    public function mount(): void
    {
        $this->external_start_date = Carbon::today()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'max:255', Rule::unique('dealer', 'nik')->where('market_id', Auth::user()->market_id)],
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
        $this->stall_term_choice = [];
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
        $this->stall_term_choice = [];
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
            ->where('pt.dealer_condition', $this->dealerCondition())
            ->when(Auth::user()?->market_id, fn ($q, $m) => $q->where('spt.market_id', $m))
            ->orderBy('pt.price')
            ->get(['spt.sptid', 'pt.ptid', 'pt.term_name', 'pt.price', 'pt.frequency', 'pt.interval_count'])
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Tahap 1: validasi penuh lalu tampilkan modal konfirmasi.
     * Data BELUM disimpan apa pun sampai user menekan konfirmasi.
     */
    public function save(): void
    {
        if (! $this->validateForSave()) {
            return;
        }

        $this->showSaveConfirm = true;
    }

    /** Tahap 2: user mengonfirmasi → simpan pedagang + generate tagihan. */
    public function confirmSave(BillGenerationService $billService): void
    {
        $this->showSaveConfirm = false;

        // Validasi ulang: kondisi bisa berubah selagi modal terbuka (mis. lapak keburu tersewa).
        if (! $this->validateForSave()) {
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
                        'sptid' => $this->stall_term_choice[$sid] ?? null,
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
        $this->redirectBack('dealers.index');
    }

    /** Seluruh validasi + guard sebelum simpan. Return false bila ada yang gagal. */
    protected function validateForSave(): bool
    {
        // Normalisasi tanggal opsional kosong ('') → null. Input date yang diisi lalu dikosongkan
        // mengirim '' (lolos rule nullable|date) dan ditolak kolom DATE saat insert → 500.
        $this->rent_end_date = $this->rent_end_date ?: null;

        // Guard premium: cegah pembuatan pedagang eksternal oleh akun non-premium.
        if ($this->cond_external && ! auth()->user()->isPremium()) {
            $this->cond_external = false;
            $this->dispatch('premium-required');

            return false;
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

            return false;
        }

        // Tiap lapak wajib punya aturan bayar terpilih yang valid: milik lapak & cocok kondisi
        // pedagang. Ini sekaligus menolak lapak yang tak punya aturan bayar sesuai kondisi.
        foreach ($this->selected_stalls as $sid) {
            $chosen = $this->stall_term_choice[$sid] ?? null;
            $validSptids = array_map(fn ($o) => (int) $o['sptid'], $this->stallTermOptions($sid));

            if (! $chosen || ! in_array((int) $chosen, $validSptids, true)) {
                $this->addError('selected_stalls', 'Setiap lapak harus dipilih satu aturan bayar yang sesuai kondisi pedagang.');

                return false;
            }
        }

        return true;
    }

    /**
     * Impor massal pedagang dari Excel (migrasi). Semua baris divalidasi dulu; bila ada
     * satu error saja, TIDAK ada yang disimpan (all-or-nothing) dan daftar error ditampilkan.
     * Bila bersih, seluruh baris disimpan dalam 1 transaksi berikut penyewaan/langganan + tagihannya.
     */
    public function importExcel(BillGenerationService $bills): void
    {
        $this->importErrors = [];
        $this->validate(['import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);

        $rows = Excel::toArray(new DealersImport, $this->import_file)[0] ?? [];

        $parsed = [];        // baris valid siap simpan
        $errors = [];
        $seenNiks = [];      // NIK yang sudah muncul di file → deteksi duplikat dalam file
        $claimedStalls = []; // sid yang sudah diklaim baris sebelumnya

        foreach ($rows as $i => $row) {
            $line = $i + 2; // +1 header, +1 index-0

            // Lewati baris yang seluruh selnya kosong.
            if (collect($row)->filter(fn ($v) => trim((string) $v) !== '')->isEmpty()) {
                continue;
            }

            $nik = trim((string) ($row['nik'] ?? ''));
            $name = trim((string) ($row['nama'] ?? ''));
            $birth = $this->parseImportDate($row['tanggal_lahir'] ?? null);
            $address = trim((string) ($row['alamat'] ?? ''));
            $phone = trim((string) ($row['telepon'] ?? ''));
            $phone2 = trim((string) ($row['telepon_2'] ?? '')) ?: null;
            $product = trim((string) ($row['jenis_dagangan'] ?? '')) ?: null;
            $letterNo = trim((string) ($row['no_surat'] ?? '')) ?: null;
            $condition = $this->normalizeCondition($row['kondisi'] ?? '');

            $rowErrors = [];

            if ($nik === '') {
                $rowErrors[] = 'NIK wajib diisi';
            } elseif (isset($seenNiks[$nik])) {
                $rowErrors[] = "NIK $nik duplikat dengan baris {$seenNiks[$nik]}";
            } elseif (Dealer::where('nik', $nik)->exists()) {
                $rowErrors[] = "NIK $nik sudah terdaftar";
            }

            if ($name === '') {
                $rowErrors[] = 'Nama wajib diisi';
            }
            if (! $birth) {
                $rowErrors[] = 'Tanggal lahir kosong/format salah';
            }
            if ($address === '') {
                $rowErrors[] = 'Alamat wajib diisi';
            }
            if ($phone === '') {
                $rowErrors[] = 'Telepon wajib diisi';
            }
            if (! in_array($condition, ['regular', 'new', 'external'], true)) {
                $rowErrors[] = "Kondisi '$condition' tidak valid (regular/new/external)";
            }

            $rental = null;   // ['sid','start','end']
            $external = null; // ['ptid','start']

            if ($condition === 'external') {
                if (! auth()->user()->isPremium()) {
                    $rowErrors[] = 'Pedagang eksternal butuh paket premium';
                }
                $termName = trim((string) ($row['aturan_bayar'] ?? ''));
                $start = $this->parseImportDate($row['tanggal_mulai'] ?? null);
                if ($termName === '') {
                    $rowErrors[] = 'Aturan bayar wajib untuk pedagang eksternal';
                } else {
                    $term = PaymentTerm::where('term_name', $termName)->where('dealer_condition', 'external')->first();
                    if (! $term) {
                        $rowErrors[] = "Aturan bayar eksternal '$termName' tidak ditemukan";
                    } elseif (! $start) {
                        $rowErrors[] = 'Tanggal mulai kosong/format salah';
                    } else {
                        $external = ['ptid' => $term->ptid, 'start' => $start->toDateString()];
                    }
                }
            } else {
                // regular/new: lapak opsional (boleh impor data pedagang saja).
                $lapak = trim((string) ($row['lapak'] ?? ''));
                if ($lapak !== '') {
                    $stall = $this->findStallByCode($lapak);
                    $start = $this->parseImportDate($row['tanggal_mulai'] ?? null);
                    $endRaw = trim((string) ($row['akhir_sewa'] ?? ''));
                    $end = $endRaw !== '' ? $this->parseImportDate($endRaw) : null;
                    $termName = trim((string) ($row['aturan_bayar'] ?? ''));

                    // Aturan bayar lapak yang cocok kondisi pedagang (satu lapak bisa punya >1).
                    $matchTerms = $stall
                        ? $stall->paymentTerms->where('dealer_condition', $condition)->values()
                        : collect();

                    // Pilih term: pakai kolom "Aturan Bayar" bila diisi; bila kosong & cuma 1 opsi, pakai itu.
                    $chosenTerm = $termName !== ''
                        ? $matchTerms->first(fn ($t) => strcasecmp(trim((string) $t->term_name), $termName) === 0)
                        : ($matchTerms->count() === 1 ? $matchTerms->first() : null);

                    if (! $stall) {
                        $rowErrors[] = "Lapak '$lapak' tidak ditemukan";
                    } elseif ($matchTerms->isEmpty()) {
                        $rowErrors[] = "Aturan bayar lapak '$lapak' tidak sesuai kondisi '$condition'";
                    } elseif ($termName === '' && $matchTerms->count() > 1) {
                        $rowErrors[] = "Lapak '$lapak' punya beberapa aturan bayar — isi kolom 'Aturan Bayar' dengan salah satu: " . $matchTerms->pluck('term_name')->join(', ');
                    } elseif (! $chosenTerm) {
                        $rowErrors[] = "Aturan bayar '$termName' bukan pilihan untuk lapak '$lapak'. Pilihan: " . $matchTerms->pluck('term_name')->join(', ');
                    } elseif (in_array($stall->sid, $claimedStalls, true)) {
                        $rowErrors[] = "Lapak '$lapak' sudah diklaim baris lain di file ini";
                    } elseif ($stall->activeRentals()->exists()) {
                        $rowErrors[] = "Lapak '$lapak' sudah tersewa";
                    } elseif (! $start) {
                        $rowErrors[] = 'Tanggal mulai kosong/format salah';
                    } elseif ($endRaw !== '' && ! $end) {
                        $rowErrors[] = 'Akhir sewa format salah';
                    } elseif ($end && $end->lt($start)) {
                        $rowErrors[] = 'Akhir sewa lebih awal dari mulai sewa';
                    } else {
                        $rental = [
                            'sid' => $stall->sid,
                            'sptid' => (int) $chosenTerm->pivot->sptid,
                            'start' => $start->toDateString(),
                            'end' => $end?->toDateString(),
                        ];
                        $claimedStalls[] = $stall->sid;
                    }
                }
            }

            if ($rowErrors) {
                $errors[] = "Baris $line: " . implode('; ', $rowErrors);

                continue;
            }

            $seenNiks[$nik] = $line;
            $parsed[] = [
                'nik' => $nik, 'name' => $name, 'birth' => $birth->toDateString(),
                'address' => $address, 'phone' => $phone, 'phone2' => $phone2,
                'product' => $product, 'letter_no' => $letterNo, 'condition' => $condition,
                'rental' => $rental, 'external' => $external,
            ];
        }

        if (empty($parsed) && empty($errors)) {
            $this->error('File tidak berisi data pedagang.');

            return;
        }

        if ($errors) {
            $this->importErrors = $errors;
            $this->error(count($errors) . ' baris bermasalah — tidak ada yang diimpor. Perbaiki lalu ulangi.');

            return;
        }

        DB::transaction(function () use ($parsed, $bills) {
            foreach ($parsed as $p) {
                $dealer = Dealer::create([
                    'nik' => $p['nik'], 'name' => $p['name'], 'birth_date' => $p['birth'],
                    'address' => $p['address'], 'phone_number_1' => $p['phone'],
                    'phone_number_2' => $p['phone2'], 'product_type' => $p['product'],
                    'status' => 'active', 'dealer_condition' => $p['condition'],
                    'letter_no' => $p['letter_no'], 'created_by' => Auth::id(),
                ]);

                if ($p['rental']) {
                    $ds = DealerStall::create([
                        'did' => $dealer->did, 'sid' => $p['rental']['sid'],
                        'sptid' => $p['rental']['sptid'],
                        'rent_start_date' => $p['rental']['start'],
                        'rent_end_date' => $p['rental']['end'],
                        'deleted' => false, 'created_by' => Auth::id(),
                    ]);
                    $bills->ensureBillsUpToDate($ds);
                } elseif ($p['external']) {
                    $ed = ExternalDealer::create([
                        'did' => $dealer->did, 'ptid' => $p['external']['ptid'],
                        'start_date' => $p['external']['start'],
                        'deleted' => false, 'created_by' => Auth::id(),
                    ]);
                    $bills->ensureExternalBillsUpToDate($ed);
                }
            }
        });

        $this->success(count($parsed) . ' pedagang berhasil diimpor.');
        $this->redirectBack('dealers.index');
    }

    /** Normalisasi kondisi + terima sinonim Indonesia. Nilai tak dikenal dibiarkan (agar divalidasi & dilaporkan). */
    protected function normalizeCondition($raw): string
    {
        $v = strtolower(trim((string) $raw));

        return match ($v) {
            '', 'regular', 'biasa', 'reguler', 'umum' => 'regular',
            'new', 'baru' => 'new',
            'external', 'eksternal', 'keliling', 'gerobak' => 'external',
            default => $v,
        };
    }

    /** Cari lapak dari kode "A01/05" / "A01-05" / "A0105". Null bila tak ketemu. */
    protected function findStallByCode(string $code): ?Stall
    {
        $raw = strtoupper(preg_replace('/\s+/', '', $code));
        if (! preg_match('/^([A-Z]\d{2})[\/\-]?(\d{2})$/', $raw, $m)) {
            return null;
        }

        return Stall::where('block', $m[1])->where('number', $m[2])->first();
    }

    /** Parse tanggal dari sel Excel (serial number atau string). Null bila kosong/invalid. */
    protected function parseImportDate($value): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function render()
    {
        $cond = $this->dealerCondition();

        // Lapak aktif yang punya ≥1 aturan bayar sesuai kondisi pedagang; muat hanya term yang cocok.
        $stalls = Stall::query()
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

        return view('livewire.dealers.create', [
            'stalls' => $stalls,
            // Detail lapak terpilih untuk ringkasan di form (dengan term yang cocok kondisi).
            'selectedStallDetails' => Stall::whereIn('sid', $this->selected_stalls)
                ->with(['paymentTerms' => fn ($q) => $q->where('dealer_condition', $cond)])
                ->orderBy('block')
                ->get(),
            // Aturan bayar untuk pedagang eksternal (tanpa lapak).
            'paymentTerms' => $this->cond_external
                ? PaymentTerm::where('dealer_condition', $cond)->orderBy('term_name')->get()
                : collect(),
        ]);
    }
}
