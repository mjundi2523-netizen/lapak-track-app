# LapakTrack — Working Agreement & Project Notes

## Cara kerja dengan user (PENTING)
- **Jadi mentor, bukan "yes-man".** Kalau ada keputusan/desain yang salah, janggal, atau ada cara yang lebih baik — katakan dan beri alasannya. Jangan asal mengiyakan.
- Kalau ragu antara beberapa pendekatan, beri rekomendasi yang jelas + trade-off-nya, bukan sekadar daftar opsi.

## Aturan keamanan repo (KRITIS)
- **JANGAN PERNAH menjalankan `git clean -fd`** (atau varian destruktif lain) di repo ini.
- Repo **belum punya commit apa pun** untuk source rebuild — semua perubahan masih *uncommitted/untracked*. `git clean` sebelumnya sudah pernah menghapus brief MVP yang terupdate. Semua yang ada di working tree adalah satu-satunya salinan.
- Sebelum operasi git destruktif apa pun (reset --hard, checkout --, clean), berhenti dan konfirmasi ke user.

## Status proyek (per 2026-06-23)
- Semua source code sempat terhapus; DB MySQL `lapak_track` utuh + datanya.
- Rebuild ikut spec `.qoder/specs/Rebuild_Lost_Source_Files_task-61c.md`. **Task 1–6 sudah selesai**, Task 7–12 belum.
- ERD lama (`terms_add_ons`) **sudah usang**. Schema asli sekarang: add-ons menempel ke **stall** lewat pivot `stall_add_ons`, bukan ke payment_terms. `bill_id` sudah ditambahkan ke `dealer_bills` & `dealer_payment`.

## Konvensi schema
- Custom PK per tabel (`ptid`, `aoid`, `sid`, `did`, `dsid`, `dbid`, `dpid`).
- `billing_status` enum: `paid | installment | unpaid | pending`.
- `frequency` enum: `daily | weekly | monthly | annual`.
- `payment_terms.interval_count` (unsigned, default 1) = pengali periode: "setiap {interval_count} {frequency}". Ditambah 2026-06-23.
- Bill (`dealer_bills`) milik **dealer_stall** (rental), bukan langsung milik dealer.

## Rencana eksekusi MVP
Lihat `.qoder/specs/LapakTrack_MVP_Plan.md` — logika billing (lazy roll-forward), status otomatis, notifikasi in-app, + checklist Task 7–12 & revisi Task 4/6.
- **Selesai:** Task 7 (layout+dashboard), 8 (auth), 10 (frontend), 12 (verifikasi). **Skip:** 9 (Filament), 11 (seeder).
- **Belum:** revisi mesin billing (lazy per-periode), `recalculateBillingStatus` 4-status, `CreateDealer` multi-lapak.

## Menjalankan app (Laragon/lokal)
- `php artisan serve` → http://127.0.0.1:8000 ; `npm run build` (atau `npm run dev`) untuk aset.
- Filament **tidak dipakai** — jangan daftarkan `AdminPanelProvider` lagi.
- Kalau muncul error "Class ... not found" saat boot / route:list nyebut file yang hilang: classmap basi → jalankan `composer dump-autoload`. Cache route lama bisa stuck → hapus `bootstrap/cache/*.php` (regenerate otomatis).
