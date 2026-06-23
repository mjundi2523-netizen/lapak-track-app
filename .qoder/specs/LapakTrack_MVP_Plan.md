# LapakTrack — Dokumen Perencanaan Eksekusi MVP

> Disusun 2026-06-23 dari sesi brainstorming. Ini melengkapi `Rebuild_Lost_Source_Files_task-61c.md` (Task 1–6 sudah selesai). Dokumen ini mendefinisikan **logika bisnis** + sisa pekerjaan (revisi Task 4/6 + Task 7–12).

---

## 0. Keputusan Terkunci

| # | Keputusan | Pilihan |
|---|-----------|---------|
| 1 | Pencatatan pembayaran | **Hanya** lewat halaman Pembayaran. Registrasi pedagang TIDAK menangkap pembayaran. |
| 2 | `billing_status` | **Diturunkan otomatis** (derived), bukan dropdown manual. |
| 3 | Notifikasi jatuh tempo | **In-app**: daftar di Dashboard + filter di halaman Tagihan. Tanpa cron/email. |
| 4 | Generate tagihan berulang | **Lazy roll-forward**: hanya generate sampai hari ini, saat aplikasi dibuka. Idempoten. |
| 5 | Hosting | **Laragon lokal** (nyala jam kerja cukup — lazy generation tidak butuh server 24 jam). |
| 6 | Lapak harian | **Tagihan dicatat tiap hari** (1 baris `dealer_bills` per hari). Ditangani **seragam** oleh engine (daily/weekly/monthly/annual). Iuran 1jt = add-on `frequency=annual`. Tidak ada special-case. |

---

## 1. Mesin Billing (REVISI `BillGenerationService` — Task 4)

### 1.1 Masalah implementasi sekarang
`generateBillsForDealerStall()` saat ini membuat **1 tagihan MTR untuk seluruh masa sewa** (period_start → +1 tahun). Ini harus diganti menjadi **per-periode + lazy catch-up**.

### 1.2 Konsep: lazy catch-up (idempoten)
Satu method `ensureBillsUpToDate(DealerStall $ds)` yang:
1. Untuk sewa aktif (`deleted = false`), tentukan `anchor = rent_start_date` dan `end = min(rent_end_date ?? today, today)`.
2. **MTR (sewa pokok)** — iterasi periode dari `anchor` s/d `end` sesuai `stall.paymentTerm.frequency`. Untuk tiap periode, **buat tagihan jika belum ada**.
3. **ATR (add-on)** — kelompokkan add-on lapak berdasarkan `frequency`. Untuk tiap frekuensi, iterasi periode dari `anchor` s/d `end`, total = SUM(harga add-on frekuensi itu). Buat jika belum ada.

### 1.3 Idempotensi (PENTING — keterbatasan schema)
`dealer_bills` **tidak punya kolom tipe** (MTR/ATR). Cek "sudah ada atau belum" dilakukan via prefix `bill_id` + periode:
```php
DealerBill::where('dsid', $dsid)
    ->where('period_start', $periodStart)
    ->where('bill_id', 'like', 'MTR-%')   // atau 'ATR-%'
    ->exists();
```
> **Rekomendasi (opsional, non-destruktif):** tambah kolom `bill_type enum('MTR','ATR') ` + `aoid nullable` ke `dealer_bills` lewat migrasi baru agar query jauh lebih bersih dan ATR bisa per-add-on. Tidak menghapus data. Putuskan saat eksekusi.

### 1.4 Perhitungan periode (calendar-aligned)
Panjang periode = `frequency` × **`interval_count`** (kolom baru di `payment_terms`, default 1). Mis. `monthly` + `interval_count=3` → periode 3 bulan.

| frequency | panjang 1 satuan |
|-----------|------------------|
| daily | 1 hari |
| weekly | 1 minggu (Senin–Minggu) |
| monthly | 1 bulan kalender |
| annual | 1 tahun (1 Jan–31 Des) |

`period_end = period_start + (interval_count × satuan) − 1 hari`. Periode pertama dipotong (clip) ke `rent_start_date`.

> **Catatan volume (lapak harian):** karena tagihan harian dicatat tiap hari, satu lapak harian menghasilkan ±365 baris/tahun. Aman karena lazy catch-up hanya generate sampai hari ini & idempoten, TAPI: (a) kalau aplikasi tidak dibuka berhari-hari, sekali buka akan generate banyak baris sekaligus (tetap aman), (b) halaman Tagihan/Pembayaran **wajib pakai paginasi + filter** agar tidak berat.

### 1.5 Jatuh tempo (`due_date`)
`due_date = period_start + grace`. Default (tunable):

| frequency | grace |
|-----------|-------|
| daily | 0 hari |
| weekly | 3 hari |
| monthly | 10 hari |
| annual | 30 hari |

### 1.6 Kapan dipanggil
- Saat registrasi pedagang (`CreateDealer`) untuk tiap lapak yang dipilih.
- Saat halaman **Dashboard**, **Tagihan**, dan **Pembayaran** dirender → panggil catch-up untuk semua sewa aktif (lazy). Idempoten, jadi aman dipanggil berkali-kali.
- (Opsional nanti) command `php artisan bills:generate` untuk dijadwalkan via cron Laragon.

---

## 2. Aturan Status Tagihan (REVISI `DealerBill::recalculateBillingStatus()`)

Status **100% diturunkan**. `paid = SUM(paid_amount WHERE is_voided = false)`:

| Kondisi | Status |
|---------|--------|
| `paid >= total_amount` | `paid` |
| `0 < paid < total_amount` | `installment` |
| `paid == 0` DAN `due_date <= today` | `unpaid` (terlambat) |
| `paid == 0` DAN `due_date > today` | `pending` (belum jatuh tempo) |

> Ini memakai keempat nilai enum dan tidak bisa desync. Method `recalculateBillingStatus()` saat ini belum membedakan `pending`/`unpaid` lewat `due_date` — perlu diperbarui sesuai tabel di atas. Panggil tiap kali pembayaran dibuat/di-void.

---

## 3. Halaman MVP — Spesifikasi Perilaku

### 3.1 Registrasi Pedagang (REVISI `CreateDealer`)
- CRUD identitas pedagang (sudah ada).
- **GAP: dukung pilih ≥1 lapak.** Ubah `selected_stall` (single) → array; buat 1 `dealer_stall` per lapak; panggil `ensureBillsUpToDate` untuk masing-masing.
- TIDAK ada field pembayaran/billing_status di sini (keputusan #1, #2).
- Modal pemilih lapak: hanya lapak `is_active = true` & belum tersewa aktif.

### 3.2 Lapak (`Stalls`)
- CRUD blok, deskripsi, is_active.
- Modal pilih **1 aturan bayar** (`ptid`).
- Checkbox **add-on** (mengisi pivot `stall_add_ons`).

### 3.3 Aturan Bayar (`PaymentTerms`)
- Form: `term_name`, `frequency (daily|weekly|monthly|annual)`, `interval_count` (✅ sudah ditambahkan), `price`.

### 3.4 Biaya Lain-lain (`AddOns`)
- Form: `add_on`, `frequency`, `price`.

### 3.5 Pembayaran (`Payments`) — satu-satunya pintu bayar
- Tabel: nama pedagang, NIK, lapak, total tagihan, sudah dibayar, sisa, jatuh tempo, status.
- **Sisa = `total_amount` − SUM(`paid_amount` non-void)** (BUKAN dari `payment_terms.price`).
- Buat pembayaran → generate `bill_id` (PMT) via `BillIdGenerator` → panggil `recalculateBillingStatus()`.
- Void: set `is_voided/voided_at/voided_by/voided_reason` → recalculate.

### 3.6 Dashboard
- Lapak: total / aktif / terisi / kosong.
- Pedagang: aktif / non-aktif.
- Tagihan: paid / installment / unpaid (terlambat) / pending.
- **Notifikasi**: daftar tagihan `due_date <= today AND status != 'paid'`.

---

## 4. Sisa Pekerjaan (Checklist Eksekusi)

### Revisi (kode Task 4 & 6 yang sudah ada)
- [ ] `BillGenerationService` → ganti ke lazy per-periode `ensureBillsUpToDate` (§1).
- [ ] `DealerBill::recalculateBillingStatus()` → aturan 4-status pakai `due_date` (§2).
- [ ] `CreateDealer` → multi-lapak (§3.1).
- [ ] `IndexBills`/`IndexPayments` → sisa pakai `total_amount`, panggil catch-up.

### Task 7 — Layout & Dashboard ✅
- [x] `layouts/sidebar.blade.php` (x-menu activate-by-route, 2 grup), komponen Livewire `Dashboard` + view (stats lapak/pedagang/tagihan + notifikasi jatuh tempo). Route `/dashboard` → `App\Livewire\Dashboard`.
- [x] Fix `app.blade.php`: logout jadi form POST (sebelumnya `wire:click` di Blade biasa — tidak jalan).

### Task 8 — Auth (Breeze 2.4.2) ✅
- [x] Base `Controller` + 9 controller auth + `LoginRequest` + view auth (login/register/forgot/reset/verify/confirm) + `components/guest-layout.blade.php` (MaryUI).
- [x] Route profil → `ShowProfile` (drop `ProfileController` & route update/destroy yang tak terpakai). Pesan validasi di-Indonesia-kan (tanpa lang file).

### Task 9 — Filament Admin — **DI-SKIP** (tidak dipakai). `AdminPanelProvider` dilepas dari `bootstrap/providers.php`.

### Task 10 — Frontend Assets ✅
- [x] `package.json`, `vite.config.js`, `resources/css/app.css` (Tailwind v4 + DaisyUI + @source MaryUI), `resources/js/app.js`. `npm run build` sukses.

### Task 11 — DatabaseSeeder — **DI-SKIP** (data sudah ada).

### Task 12 — Verifikasi ✅
- [x] `migrate` → "Nothing to migrate"; `route:list` → 49 route; `view:cache` kompilasi tanpa error.
- [x] Data utuh: dealers=20, stalls=25, bills=57, payments=35.
- [x] HTTP: `/login` 200, `/`→`/dashboard`, `/dashboard`→`/login` (auth), `/register` 200. Semua halaman Livewire utama render OK.

> **Fix tambahan saat eksekusi** (root cause bug boot): `public/index.php` tidak memuat `vendor/autoload.php` → diganti front controller standar. Classmap composer basi (file Breeze/Filament lama yang terhapus masih tercatat) → `composer dump-autoload`. Cache route lama (`bootstrap/cache/routes-v7.php`) menyimpan route Filament → dihapus.

---

## 5. Catatan / Asumsi untuk Dikonfirmasi
1. **Kolom tipe tagihan** (§1.3) — pakai prefix `bill_id` atau tambah `bill_type`? Default: prefix dulu, tambah kolom kalau ATR per-add-on dibutuhkan.
2. ~~**"satuan (angka)"** di brief poin #1 belum ada kolomnya di `payment_terms`.~~ ✅ **SELESAI** — kolom `interval_count` (unsigned, default 1) ditambahkan via migrasi `2026_06_23_000000_add_interval_count_to_payment_terms`. Engine billing harus memakainya untuk panjang periode (§1.4).
3. **Grace period jatuh tempo** (§1.5) — angka default, silakan sesuaikan.
4. **`dealer.status`** active/inactive — untuk MVP di-set manual; otomatisasi (mis. inactive kalau semua sewa dihapus) menyusul.
