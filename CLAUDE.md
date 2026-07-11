# LapakTrack — Working Agreement & Project Notes

## Cara kerja dengan user (PENTING)
- **Jadi mentor, bukan "yes-man".** Kalau ada keputusan/desain yang salah, janggal, atau ada cara yang lebih baik — katakan dan beri alasannya. Jangan asal mengiyakan.
- Kalau ragu antara beberapa pendekatan, beri rekomendasi yang jelas + trade-off-nya, bukan sekadar daftar opsi.
- **Jangan pakai Chrome DevTools (MCP) untuk QC/testing UI** kecuali diizinkan atau diminta eksplisit oleh user pada saat itu. Untuk verifikasi manual, tanyakan dulu ke user atau gunakan cara lain (mis. baca kode, jalankan test, minta user cek langsung di browser).

## Aturan keamanan repo (KRITIS)
- **JANGAN PERNAH menjalankan `git clean -fd`** (atau varian destruktif lain) di repo ini.
- Repo sudah punya **1 base commit** (`LapakTrack — Laravel + Livewire/MaryUI app (clean history)`) dan sudah di-push (history bersih, `.env` tak ter-track, APP_KEY sudah dirotasi). **TAPI** seluruh kerja mesin billing + skema add-on fleksibel masih *uncommitted* di working tree — itu satu-satunya salinan. `git clean` sebelumnya pernah menghapus brief MVP yang terupdate.
- Sebelum operasi git destruktif apa pun (reset --hard, checkout --, clean), berhenti dan konfirmasi ke user.
- **`vault/` di-gitignore** (basic memory Obsidian) — TIDAK ter-backup git; jangan pernah hapus/timpa isinya tanpa diminta.

## Dokumentasi detail → vault Obsidian (`vault/LapakTrack/`)
@vault/LapakTrack/Index.md

Detail lengkap tiap subsistem ada di note masing-masing (peta di atas). **Sebelum mengerjakan suatu area, baca note terkait; setelah perubahan besar, update note-nya** (dan tambah aturan baru yang wajib-selalu-dipatuhi ke bagian "Aturan inti" di bawah). Jangan buka seluruh isi folder `vault/` sekaligus — cukup note yang relevan dengan area yang sedang dikerjakan.

## Status proyek (per 2026-07-11)
- **Pemasukan Tambahan** (lain-lain, di luar tagihan pedagang) ditambahkan: model `Income`/`IncomeCategory` (tabel `incomes`/`income_categories`, mirror `expenses` tanpa `status`/`rxid`), rute `incomes.*`/`income-categories.*` (premium). Sidebar Transaksi → "Pemasukan" jadi accordion (Pedagang = `payments.*` lama, Lain-lain = `incomes.*` baru). Terintegrasi ke Arus Kas & Dashboard (lihat vault `Pengeluaran & Laporan.md`).

## Status proyek (per 2026-07-07)
- Rebuild pasca-kehilangan source selesai (spec `.qoder/specs/Rebuild_Lost_Source_Files_task-61c.md`; Task 1–8, 10, 12; skip 9 Filament & 11 seeder). DB MySQL `lapak_track` utuh.
- Mesin billing tuntas & data di-regen penuh; multi-tenancy + onboarding sudah masuk. Kerja billing engine **belum di-commit** (lihat Aturan keamanan).
- ERD lama (`terms_add_ons`) usang — add-ons menempel ke **stall** via pivot `stall_add_ons`.
- **Aturan bayar (payment_terms) juga many-to-many ke stall** via pivot `stall_payment_terms` (PK `sptid`) — satu lapak bisa menawarkan >1 aturan bayar. Kolom `stall.ptid` sudah **dihapus**. Saat pedagang menyewa, `dealer_stall.sptid` menyimpan **satu** aturan bayar terpilih (FK ke pivot → dijamin milik lapak itu). Billing me-resolve term via `dealer_stall.sptid → stall_payment_terms → payment_terms` (`$ds->stallPaymentTerm->paymentTerm`), bukan lagi `stall->paymentTerm`. Jalur eksternal tetap pakai `external_dealers.ptid` (terpisah).

## Aturan inti (wajib dipatuhi lintas area — detail di vault)
- **Multi-tenancy aktif**: JANGAN filter `market_id` manual di query Eloquent (global scope `BelongsToMarket` + auto-isi saat create). Query mentah `DB::table`/`DB::raw` TIDAK kena scope → WAJIB filter manual. Service generator set `market_id` eksplisit dari parent (aman di console tanpa Auth). Scope inert bila user null / `market_id` null (developer/console → lintas market).
- **`billing_status` enum** `paid|installment|unpaid|pending|cancelled`; `cancelled` = terminal (tak disentuh `recalculateBillingStatus()`, dikecualikan dari hitungan/notifikasi/pemilih bayar). PK custom per tabel (`ptid`,`aoid`,`sid`,`did`,`dsid`,`dbid`,`dpid`,`edid`,`mid`,`saoid`,`sptid`,`imid`,`icid`…).
- **Form create/edit/void baru WAJIB**: `use ReturnsBack;` + `redirectBack('fallback')` + tombol Batal `backHref()`; filter index baru diberi `#[Url]`. Guard dirty-form global otomatis selama pakai `x-form wire:submit`.
- **Aksi destruktif baru** → minimal `wire:confirm`; yang berdampak generate tagihan → pola modal konfirmasi 2 tahap (validasi ulang di `confirmSave()`).
- **`CreatePayment` menolak over-payment** — belum ada konsep saldo, jangan diakali.
- **PK JANGAN PERNAH tampil di URL** (path/query string): model ber-route WAJIB `use HasObfuscatedId;` (Sqids). Di blade pakai `$model->obfuscated_id`; konteks non-model `encodeKey()`/`decodeKey()`. JANGAN ubah `config/sqids.php` setelah production (semua URL lama 404). Detail di vault.
- **Fitur premium baru**: route middleware `premium` + item sidebar `'premium'=>true`; aksi in-page `$this->dispatch('premium-required')`.
- **Desain kartu pedagang** (`resources/views/dealers/_letter.blade.php`) dibuat user — jangan diubah tanpa diminta.
- **Filament tidak dipakai** — jangan daftarkan `AdminPanelProvider`.
- Ubah `resources/js/app.js` atau CSS → `npm run build`.

## Menjalankan app (Laragon/lokal)
- `php artisan serve` → http://127.0.0.1:8000 ; `npm run build` (atau `npm run dev`) untuk aset.
- Kalau muncul error "Class ... not found" saat boot / route:list nyebut file yang hilang: classmap basi → `composer dump-autoload`. Cache route lama bisa stuck → hapus `bootstrap/cache/*.php` (regenerate otomatis).

## Penjadwalan (cron) — generate tagihan & pengeluaran rutin
- **Timezone app = `Asia/Jakarta`** (`config/app.php`), supaya `today()`/`now()`/scheduler konsisten WIB. Jadwal di `routes/console.php`: `bills:generate` & `expenses:generate` `->dailyAt('00:00')` (idempoten, lintas-market via console). Catch-up lazy di `mount()` tetap dipertahankan sebagai jaring pengaman.
- Butuh 1 pemicu OS yang memanggil `php artisan schedule:run` tiap menit:
  - **Linux (produksi)**: `* * * * * cd /path/ke/app && php artisan schedule:run >> /dev/null 2>&1`
  - **Windows/Laragon (lokal)**: Task Scheduler jalankan `schedule-run.bat` (di root repo) tiap 1 menit. Mesin harus menyala.
- Uji: `php artisan schedule:list`, `php artisan bills:generate`, `php artisan expenses:generate`; output ke `storage/logs/schedule.log`.
