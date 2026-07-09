# LapakTrack — Working Agreement & Project Notes

## Cara kerja dengan user (PENTING)
- **Jadi mentor, bukan "yes-man".** Kalau ada keputusan/desain yang salah, janggal, atau ada cara yang lebih baik — katakan dan beri alasannya. Jangan asal mengiyakan.
- Kalau ragu antara beberapa pendekatan, beri rekomendasi yang jelas + trade-off-nya, bukan sekadar daftar opsi.

## Aturan keamanan repo (KRITIS)
- **JANGAN PERNAH menjalankan `git clean -fd`** (atau varian destruktif lain) di repo ini.
- Repo sudah punya **1 base commit** (`LapakTrack — Laravel + Livewire/MaryUI app (clean history)`) dan sudah di-push (history bersih, `.env` tak ter-track, APP_KEY sudah dirotasi). **TAPI** seluruh kerja mesin billing + skema add-on fleksibel masih *uncommitted* di working tree — itu satu-satunya salinan. `git clean` sebelumnya pernah menghapus brief MVP yang terupdate.
- Sebelum operasi git destruktif apa pun (reset --hard, checkout --, clean), berhenti dan konfirmasi ke user.
- **`vault/` di-gitignore** (basic memory Obsidian) — TIDAK ter-backup git; jangan pernah hapus/timpa isinya tanpa diminta.

## Dokumentasi detail → vault Obsidian (`vault/LapakTrack/`)
Detail lengkap tiap subsistem dipindah ke vault (mulai `vault/LapakTrack/Index.md`). **Sebelum mengerjakan suatu area, baca note terkait; setelah perubahan besar, update note-nya** (dan tambah aturan baru yang wajib-selalu-dipatuhi ke bagian "Aturan inti" di bawah):
- `Multi-Tenancy (Market).md` — scoping per-market, `BelongsToMarket`, onboarding/approval
- `Konvensi Schema.md` — PK custom, enum, tabel domain, pedagang eksternal, format lokasi lapak
- `Mesin Billing.md` — lazy roll-forward, tipe MTR/MAT/AAT/ATR/EXT, cursor stream, status MVP
- `Occupancy & Akhiri Sewa.md` — definisi terisi/kosong, `deleted`, alur akhiri sewa
- `Fitur Premium.md` — gerbang 3 lapis, modal premium
- `Tema & Dark Mode.md` — cyan `#0891b2`, dark mode di DB, DaisyUI/MaryUI
- `Cetak Dokumen.md` — kartu pedagang, kwitansi, invoice; pola langsung-print & print fix
- `Pengeluaran & Laporan.md` — expenses/void, laporan, pengeluaran rutin auto-generate
- `Pola Form & Konfirmasi.md` — ReturnsBack, `#[Url]`, konfirmasi 2 tahap, guard dirty-form
- `Obfuscated ID (Sqids).md` — PK tidak pernah tampil di URL; trait `HasObfuscatedId`, titik decode manual

## Status proyek (per 2026-07-07)
- Rebuild pasca-kehilangan source selesai (spec `.qoder/specs/Rebuild_Lost_Source_Files_task-61c.md`; Task 1–8, 10, 12; skip 9 Filament & 11 seeder). DB MySQL `lapak_track` utuh.
- Mesin billing tuntas & data di-regen penuh; multi-tenancy + onboarding sudah masuk. Kerja billing engine **belum di-commit** (lihat Aturan keamanan).
- ERD lama (`terms_add_ons`) usang — add-ons menempel ke **stall** via pivot `stall_add_ons`.

## Aturan inti (wajib dipatuhi lintas area — detail di vault)
- **Multi-tenancy aktif**: JANGAN filter `market_id` manual di query Eloquent (global scope `BelongsToMarket` + auto-isi saat create). Query mentah `DB::table`/`DB::raw` TIDAK kena scope → WAJIB filter manual. Service generator set `market_id` eksplisit dari parent (aman di console tanpa Auth). Scope inert bila user null / `market_id` null (developer/console → lintas market).
- **`billing_status` enum** `paid|installment|unpaid|pending|cancelled`; `cancelled` = terminal (tak disentuh `recalculateBillingStatus()`, dikecualikan dari hitungan/notifikasi/pemilih bayar). PK custom per tabel (`ptid`,`aoid`,`sid`,`did`,`dsid`,`dbid`,`dpid`,`edid`,`mid`…).
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
