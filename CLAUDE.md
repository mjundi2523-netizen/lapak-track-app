# LapakTrack — Working Agreement & Project Notes

## Cara kerja dengan user (PENTING)
- **Jadi mentor, bukan "yes-man".** Kalau ada keputusan/desain yang salah, janggal, atau ada cara yang lebih baik — katakan dan beri alasannya. Jangan asal mengiyakan.
- Kalau ragu antara beberapa pendekatan, beri rekomendasi yang jelas + trade-off-nya, bukan sekadar daftar opsi.

## Aturan keamanan repo (KRITIS)
- **JANGAN PERNAH menjalankan `git clean -fd`** (atau varian destruktif lain) di repo ini.
- Repo sudah punya **1 base commit** (`LapakTrack — Laravel + Livewire/MaryUI app (clean history)`) dan sudah di-push (history bersih, `.env` tak ter-track, APP_KEY sudah dirotasi). **TAPI** seluruh kerja mesin billing + skema add-on fleksibel masih *uncommitted* di working tree — itu satu-satunya salinan. `git clean` sebelumnya pernah menghapus brief MVP yang terupdate.
- Sebelum operasi git destruktif apa pun (reset --hard, checkout --, clean), berhenti dan konfirmasi ke user.

## Status proyek (per 2026-06-27)
- Semua source code sempat terhapus; DB MySQL `lapak_track` utuh + datanya.
- Rebuild ikut spec `.qoder/specs/Rebuild_Lost_Source_Files_task-61c.md`. **Task 1–8, 10, 12 selesai.** Skip 9 (Filament) & 11 (seeder).
- Mesin billing tuntas: lazy roll-forward 4 tipe tagihan (MTR/MAT/AAT/ATR), skema add-on dengan anchor fleksibel (`is_rent_date`/`start_date`). Data sudah di-regen penuh. Lihat bagian "Mesin billing" di bawah. **Belum di-commit.**
- ERD lama (`terms_add_ons`) **sudah usang**. Schema asli sekarang: add-ons menempel ke **stall** lewat pivot `stall_add_ons`, bukan ke payment_terms. `bill_id` sudah ditambahkan ke `dealer_bills` & `dealer_payment`.

## Konvensi schema
- Custom PK per tabel (`ptid`, `aoid`, `sid`, `did`, `dsid`, `dbid`, `dpid`).
- `billing_status` enum: `paid | installment | unpaid | pending | cancelled`. `cancelled` = status terminal (tunggakan yang dibatalkan saat sewa diakhiri); `recalculateBillingStatus()` tidak menyentuhnya, dan dikecualikan dari hitungan/notifikasi/pemilih bayar. Ditambah 2026-06-27.
- `frequency` enum: `daily | weekly | monthly | annual`.
- `payment_terms.interval_count` (unsigned, default 1) = pengali periode: "setiap {interval_count} {frequency}". Ditambah 2026-06-23.
- **`dealer.dealer_condition`** & **`payment_terms.dealer_condition`** enum `regular | new | external` (default `regular`). Ditambah 2026-06-28 (menggantikan `is_new` bool). **Pemilihan lapak difilter ketat**: `dealer.dealer_condition` harus = `paymentTerm.dealer_condition` (`CreateDealer`/`EditDealer` filter + guard mismatch di `save`). Form Pedagang & Aturan Bayar punya **2 checkbox** "Pedagang baru" (`cond_new`→`new`) & "Pedagang eksternal" (`cond_external`→`external`), `wire:model.live`, mutually-exclusive via `updatedCondNew/External` (reset pilihan lapak). Tanpa centang = `regular`.
- **Pedagang eksternal** (tukang gerobak/keliling) = `external`: **tidak menyewa lapak**. Punya tabel **`external_dealers`** (edid, did, ptid, start_date, end_date, deleted) — sejajar `dealer_stall` tapi relasi langsung ke `payment_terms`. Form pedagang (Create/Edit) eksternal → picker Aturan Bayar (difilter `dealer_condition`) + **`start_date` di-input user** → buat baris `external_dealers` + generate tagihan. `selected_stalls` dilonggarkan (`nullable`). Kolom `dealer.ptid` lama sudah **di-drop**.
- **`dealer_bills`**: `dsid` jadi **nullable**, tambah **`edid`** (nullable, FK `external_dealers`) — tepat satu dari (`dsid`,`edid`) terisi. `bill_type` tambah **`EXT`**. Engine: `ensureExternalBillsUpToDate()` (anchor `start_date`, term dari `ptid`, cursor `(edid,frequency)`) + loop di `ensureAllActive()`.
- **Resolusi pemilik tagihan 2-jalur**: `DealerBill::$holder` (accessor) = `dealerStall.dealer ?? externalDealer.dealer`; `DealerBill::$location_label` = blok lapak / "Eksternal". Semua layar (IndexBills/Payments, ShowBill/Payment, Dashboard, CreatePayment, Void) eager-load kedua relasi + search nama lewat dua relasi.
- `dealer.letter_no` (nullable) = No. surat/kartu pedagang (input di Create/Edit; fallback auto di surat).
- `dealer_bills.bill_type` enum: `MTR | MAT | AAT | ATR` (lihat "Mesin billing"). `dealer_bills.aoid` (nullable, FK→`add_ons`) terisi hanya untuk ATR.
- `add_ons.is_rent_date` (bool, default true) + `add_ons.start_date` (date, nullable) — anchor penagihan add-on. Ditambah 2026-06-27.
- Bill (`dealer_bills`) milik **dealer_stall** (rental), bukan langsung milik dealer.
- **Lokasi lapak (`stall`)** = 2 kolom: **`block`** (1 huruf + 2 angka, mis. `A01`, regex `^[A-Z]\d{2}$`) + **`number`** (2 angka, mis. `05`, regex `^\d{2}$`). Unik komposit (`block`,`number`) via index `stall_block_number_unique` (unique block-saja sudah di-drop). Accessor **`Stall::$code`** = `"{block} / {number}"` (mis. "A01 / 05") — dipakai di semua tampilan (index/show/denah/picker pedagang/surat/kwitansi, `DealerBill::$location_label`). Surat: nomor surat fallback pakai `{block}-{number}`; field "Kios/Los Nomor" pakai `code`. Create/Edit menormalkan input (block uppercase, number pad 2 digit). **Denah** dikelompokkan per `block`, tiap sel = `number`. Ditambah 2026-06-28 (gantikan `block` bebas lama "A-001"; data lama dikonversi: tiap grup huruf → block `{huruf}01`, angka jadi number). **Catatan:** ada 1 lapak uji (sid 55, desc "tes", tanpa rental) yang block lamanya tak berpola → fallback `Z01/01`; hapus bila tak dipakai.
- **`stall.size`** (varchar(100), nullable) = ukuran fisik lapak (mis. "3x4 m"), input di Create/Edit, tampil di detail + surat ("Ukuran"). Ditambah 2026-06-28. `stall.description` jadi catatan opsional.

## Fitur premium (gerbang akses) — ditambah 2026-06-29
- **`users.is_premium`** (bool, default false; cast boolean; helper `User::isPremium()`). Diset manual di DB (belum ada UI billing/upgrade).
- **Fitur digerbang**: semua Laporan (`reports.*`), Denah Lapak (`stalls.map`), Pengeluaran (`expenses.*`), Kategori Pengeluaran (`expense-categories.*`), dan fitur **Pedagang Eksternal** di Create/Edit pedagang.
- **3 lapis**: (1) **Middleware `premium`** (`EnsurePremium`, alias di `bootstrap/app.php`) membungkus route premium di `routes/web.php` — non-premium di-redirect ke `dashboard` dgn flash `premium_required`. (2) **Sidebar** (`layouts/sidebar.blade.php`): item bertanda `'premium'=>true` jadi **tombol** `@click="$dispatch('premium-required')"` + ikon `s-lock-closed` untuk non-premium (bukan link). (3) **Checkbox eksternal** (`CreateDealer`/`EditDealer`): `updatedCondExternal` & `save()` reset + `$this->dispatch('premium-required')` bila non-premium.
- **Modal global** `partials/premium-modal.blade.php` (di-include `layouts/app.blade.php`): Alpine, listen `premium-required.window` (Alpine `$dispatch` & Livewire `$this->dispatch` sama-sama memicu), auto-open bila `session('premium_required')`. CTA = WhatsApp (`config('lapak.developer_whatsapp')`, env `DEVELOPER_WHATSAPP`, default nomor asia). Config: `config/lapak.php`.
- Menambah fitur premium baru: tandai route dgn middleware `premium` + item sidebar `'premium'=>true`. Aksi in-page: `$this->dispatch('premium-required')`.

## Rencana eksekusi MVP
Lihat `.qoder/specs/LapakTrack_MVP_Plan.md` — logika billing (lazy roll-forward), status otomatis, notifikasi in-app, + checklist Task 7–12 & revisi Task 4/6.
- **Selesai:** Task 7 (layout+dashboard), 8 (auth), 10 (frontend), 12 (verifikasi). **Skip:** 9 (Filament), 11 (seeder).
- **Selesai juga:** revisi mesin billing (lazy roll-forward, cursor `max(period_end)` per stream), `DealerBill::deriveStatus()` 4-status, `CreateDealer` multi-lapak, command `bills:generate`, catch-up di `mount()` Dashboard/Tagihan/Pembayaran.

## Mesin billing (cara kerja sekarang)
- Tagihan dibuat per-periode secara **lazy** saat halaman dibuka (idempoten). Konvensi: `period_end = start + interval`, `due_date = period_end`.
- **4 tipe tagihan** (`dealer_bills.bill_type`) — label turunan dari **jumlah komponen**, bukan dari `is_rent_date`:
  - `MTR` = sewa saja (berdiri sendiri)
  - `ATR` = 1 add-on saja (berdiri sendiri) — berlaku baik `is_rent_date=true` maupun `false`
  - `MAT` = sewa + ≥1 add-on(`is_rent_date=true`) frekuensi sama, digabung 1 baris
  - `AAT` = ≥2 add-on(`is_rent_date=true`) frekuensi sama tanpa sewa, digabung 1 baris
- **Cursor stream (kunci idempotensi):**
  - MTR/MAT/AAT/ATR-tanpa-aoid (rent-anchored, `is_rent_date=true`): key = `(dsid, frequency, aoid IS NULL)` → 1 stream per frekuensi.
  - ATR-dengan-aoid (`is_rent_date=false`): key = `(dsid, aoid)`, anchor = `start_date` (periode sebelum `rent_start` di-skip/clamp).
  - `bill_type` adalah label saja, **bukan** bagian kunci cursor → transisi MTR↔MAT↔AAT mulus saat add-on ditambah/dihapus tanpa dobel tagihan.
- `add_ons` punya `is_rent_date` (bool, default true) + `start_date` (date, nullable). UI Create/Edit AddOn: checkbox "Ikut tanggal sewa"; field `start_date` muncul hanya saat tidak diceklis.
- Add-on "tiap 1 Jan": cukup set `is_rent_date=false, start_date=1 Jan` — tidak ada special-case di engine.
- Status diturunkan murni dari bayar vs `due_date`. `pending→unpaid` otomatis saat due lewat.
- **Data sekarang:** MTR 42 / MAT 191 / AAT 3 / ATR 763 (regen penuh, payments di-reset).
- **Perubahan ini BELUM di-commit.**

## Occupancy & berhenti sewa
- **`stall.is_active`** = lapak ada tapi (sementara) tak bisa dipakai; bisa diaktifkan lagi. **Beda** dari occupancy.
- **Occupancy (terisi/kosong)** = `Stall::activeRentals` → ada `dealer_stall` dengan `deleted=false` DAN `rent_start_date <= hari ini < rent_end_date` (atau `rent_end_date` NULL). **Eksklusif di ujung**: pada hari `rent_end_date` lapak sudah dianggap kosong. Dipakai dashboard, lapak tersedia di `CreateDealer`/`EditDealer`, tenant di `ShowStall`.
- **`dealer_stall.deleted`** = soft-delete record rental (mis. **salah input**), **terpisah** dari occupancy. **TODO (belum dibuat):** aksi "Hapus rental (salah input)" di `ShowDealer` yang set `deleted=true`. Saat ini kolom dorman (tidak ada penulis `deleted=true`); hanya `CreateDealer` set `false`.
- **"Akhiri Sewa"** (`ShowDealer` → modal): set `rent_end_date` (lapak otomatis kosong saat tanggal lewat) + `ensureBillsUpToDate` (tagihan final) + pilihan tunggakan: **biarkan jadi utang** atau **`cancel`** (unpaid/pending → `cancelled`). Status pedagang **tidak** diubah otomatis (manual). Begitu `rent_end_date` di-set, tombol jadi badge "Sewa berakhir" non-aktif.
- **`Dealer::activeRentals`** = rental `deleted=false` & belum berakhir (`rent_end` NULL / `> hari ini`, eksklusif). Aturan terkait:
  - Pedagang dengan sewa aktif **tidak bisa dinonaktifkan** (`EditDealer` blokir + pesan).
  - `EditDealer`: kalau punya sewa aktif → daftar lapak **readonly**; kalau tidak → boleh **pilih lapak lagi** (buat rental baru + generate tagihan).

## Surat/Kartu Pedagang (cetak)
- Tombol **Cetak Surat** di `ShowDealer` → modal `ShowDealer::openLetter()` menampilkan **Kartu Pedagang "PASAR SWASTA NUSANTARA"** (acuan: lampiran PDF hal.1, PT. Bintang Inter Nusantara). Partial `resources/views/dealers/_letter.blade.php` (`#lt-letter`).
- Layout 2 kolom landscape: kiri = data diri (No., 10 field, ttd pedagang + pas foto), kanan = **11 peraturan** + ttd "M. FARHAN YAKOB / Direktur Utama". Print scoped sendiri (`<style>@media print … @page A4 landscape`) — tak ubah app.css, tak bentrok dgn print kwitansi (portrait).
- Field surat dari data pedagang + **rental aktif pertama** (fallback rental terbaru): Kios=block, Ukuran=stall.description, Masa Berlaku=rent_start s/d rent_end. Warga Negara "INDONESIA" & Status "Sewa/Kontrak" hardcoded. Tempat lahir belum ada di schema (hanya tgl).
- **No. surat = input bebas** `dealer.letter_no` (kolom ditambah 2026-06-27, di form Create/Edit), fallback auto `{block}/PSR-N/{romawi-bulan}/{tahun}` bila kosong.
- **Print fix (penting):** elemen `position:fixed` + halaman panjang → dulu tercetak berulang (8 halaman). Sekarang print pakai `body :not(:has(#lt-letter))… { display:none }` (mengkerutkan tinggi jadi 1 halaman) + overlay dijadikan `static`. Pola `:has()` butuh engine Chromium (print preview Edge/Chrome OK). **TODO:** kwitansi (`#lt-receipt`, masih `position:fixed` di app.css) berpotensi bug sama — terapkan pola serupa bila perlu.

## Surat/Kartu Pedagang (cetak)
- Tombol **Cetak Surat** di `ShowDealer` (`openLetter`) → modal partial `resources/views/dealers/_letter.blade.php` (`#lt-letter`), berisi Kartu Pedagang "PASAR SWASTA NUSANTARA" (data diri + 11 peraturan + ttd), acuan lampiran PDF hal.1. **Sudah dibuat user — jangan diubah tanpa diminta.**
- Print di-scope lewat `@media print` di `app.css` (`body :not(:has(.lt-print-overlay))… display:none`; `#lt-letter` named-page **landscape**, kwitansi portrait). No. surat dari `dealer.letter_no` (fallback auto).

## Pengeluaran & Laporan (ditambah 2026-06-29)
- **`expense_categories`** (ecid, name) = master kategori. CRUD di Master Data (`expense-categories.*`).
- **`expenses`** (xpid, ecid, title, amount, expense_date, payment_method, note, **void: is_voided/voided_reason/voided_at/voided_by**). **Create + Void, tanpa Edit** (pola sama `dealer_payment`). Menu Transaksi → "Pengeluaran" (`expenses.*`): Index filter (cari/kategori/rentang tanggal/status) + kartu total + Void.
- **Laporan Arus Kas** (`reports.cash-flow`, grup Laporan): pilih tahun → tabel bulanan **Pemasukan** (Σ `dealer_payment` non-void by `payment_date`) vs **Pengeluaran** (Σ `expenses` non-void by `expense_date`) vs **Laba/Rugi** + rincian per kategori. Pemasukan mencakup tagihan sewa & eksternal (pembayaran seragam).
- **Dashboard**: kartu "Pengeluaran Bulan Ini" + "Laba Bersih" (terbayar − pengeluaran) + garis pengeluaran (merah putus-putus) di chart.
- Pengeluaran rutin = manual (belum auto-generate). Foto/nota belum ada (kolom `proof_path` bisa ditambah nanti).

## Pembayaran: aturan nominal
- **Blocking lebih-bayar**: `CreatePayment` menolak `paid_amount > sisa` (`sisa = total − Σ non-void`) + menolak tagihan `paid`/`cancelled`. Server-side (`addError`), plus `max` & tombol "Bayar penuh" (`payFull()`) di form. Belum ada konsep saldo/lebih-bayar — jangan diakali lewat over-payment.

## Pembayaran: detail & kwitansi
- **Detail** (`payments.show` → `ShowPayment`, `/pembayaran/{payment}`): Informasi Pembayaran (+ blok pembatalan bila void) & Tagihan Terkait (jenis, status, periode, total/terbayar/sisa, link ke `bills.show`). Aksi "Detail" (ikon mata) di index.
- Tiap pembayaran (non-void) punya tombol **Cetak** di index & halaman detail → `openReceipt()` membuka **modal** struk (bukan tab baru). Modal = partial `payments/_receipt-modal.blade.php` (dipakai index & detail), komponen pemanggil wajib punya `closeReceipt()`.
- Kartu struk = partial `resources/views/payments/_receipt-card.blade.php` (desain "TANDA TERIMA" dari Claude Design: serif + border ganda + `#lt-receipt`). Tombol Cetak panggil `window.print()`.
- Print di-scope lewat `@media print` global di `resources/css/app.css`: semua di-hide kecuali `#lt-receipt` (+ `.no-print` untuk tombol). Angka→kata via `App\Support\Terbilang`. Penanda tangan = user login, kota default "Bekasi" (hardcoded di partial — jadikan setting bila perlu).

## Menjalankan app (Laragon/lokal)
- `php artisan serve` → http://127.0.0.1:8000 ; `npm run build` (atau `npm run dev`) untuk aset.
- Filament **tidak dipakai** — jangan daftarkan `AdminPanelProvider` lagi.
- Kalau muncul error "Class ... not found" saat boot / route:list nyebut file yang hilang: classmap basi → jalankan `composer dump-autoload`. Cache route lama bisa stuck → hapus `bootstrap/cache/*.php` (regenerate otomatis).
