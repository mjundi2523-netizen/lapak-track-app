<?php

namespace App\Exports\Sheets;

use App\Models\PaymentTerm;
use App\Models\Stall;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Sheet "Keterangan": panduan pengisian + daftar pilihan NYATA dari market
 * yang sedang login (lapak tersedia & aturan bayar eksternal). Tidak dibaca
 * importer (importer hanya membaca sheet pertama = "Pedagang").
 */
class DealerTemplateLegendSheet implements FromArray, WithTitle
{
    public function title(): string
    {
        return 'Keterangan';
    }

    public function array(): array
    {
        $freq = ['daily' => 'hari', 'weekly' => 'minggu', 'monthly' => 'bulan', 'annual' => 'tahun'];

        $rows = [
            ['PANDUAN IMPOR PEDAGANG'],
            ['Isi data mulai baris ke-2 pada sheet "Pedagang". Jangan ubah baris judul kolom.'],
            [''],
            ['Kolom', 'Wajib?', 'Keterangan'],
            ['NIK', 'Ya', 'Nomor KTP. Harus unik (belum terdaftar & tidak dobel dalam file).'],
            ['Nama', 'Ya', ''],
            ['Tanggal Lahir', 'Ya', 'Format YYYY-MM-DD (mis. 1990-05-17).'],
            ['Alamat', 'Ya', ''],
            ['Telepon', 'Ya', 'Nomor HP utama.'],
            ['Telepon 2', 'Tidak', 'Nomor HP alternatif.'],
            ['Jenis Dagangan', 'Tidak', ''],
            ['No Surat', 'Tidak', 'Nomor surat/kartu pedagang.'],
            ['Kondisi', 'Tidak', 'regular / new / external. Kosong = regular. Lihat "Pilihan Kondisi" di bawah.'],
            ['Lapak', 'Untuk regular/new', 'Kode lapak (mis. A01/05). Harus ada & belum tersewa. Lihat "Daftar Lapak Tersedia".'],
            ['Tanggal Mulai', 'Ya bila menyewa/berlangganan', 'Mulai sewa (regular/new) atau mulai langganan (external). Format YYYY-MM-DD.'],
            ['Akhir Sewa', 'Tidak', 'Hanya untuk penyewa lapak. Kosongkan bila tanpa batas.'],
            ['Aturan Bayar', 'Untuk external', 'Nama aturan bayar eksternal (harus sama persis). Lihat "Daftar Aturan Bayar Eksternal".'],
            [''],
            ['PILIHAN KONDISI', 'Sinonim yang diterima', 'Arti'],
            ['regular', 'biasa, reguler, umum, (kosong)', 'Pedagang biasa yang menyewa lapak.'],
            ['new', 'baru', 'Pedagang baru (aturan bayar khusus), menyewa lapak.'],
            ['external', 'eksternal, keliling, gerobak', 'Pedagang keliling/gerobak tanpa lapak. Butuh paket premium.'],
            [''],
            ['CONTOH ISIAN'],
            ['Menyewa lapak (regular):', 'Kondisi=regular, Lapak=A01/05, Tanggal Mulai=2026-01-01, Aturan Bayar=(kosong)'],
            ['Pedagang eksternal:', 'Kondisi=external, Lapak=(kosong), Tanggal Mulai=2026-01-01, Aturan Bayar=<nama dari daftar di bawah>'],
            ['Data master saja (tanpa tagihan):', 'Kondisi=regular, Lapak=(kosong), Tanggal Mulai=(kosong)'],
            [''],
            ['DAFTAR LAPAK TERSEDIA (untuk kolom "Lapak")', 'Kondisi', 'Aturan Bayar Sewa'],
        ];

        $stalls = Stall::where('is_active', true)
            ->whereDoesntHave('activeRentals')
            ->with('paymentTerm')
            ->orderBy('block')
            ->orderBy('number')
            ->get();

        if ($stalls->isEmpty()) {
            $rows[] = ['(tidak ada lapak tersedia)', '', ''];
        } else {
            foreach ($stalls as $s) {
                $rows[] = [
                    $s->block . '/' . $s->number,
                    $s->paymentTerm?->dealer_condition ?? '(belum ada aturan)',
                    $s->paymentTerm?->term_name ?? '-',
                ];
            }
        }

        $rows[] = [''];
        $rows[] = ['DAFTAR ATURAN BAYAR EKSTERNAL (untuk kolom "Aturan Bayar")', 'Tarif'];

        $ext = PaymentTerm::where('dealer_condition', 'external')->orderBy('term_name')->get();

        if ($ext->isEmpty()) {
            $rows[] = ['(belum ada aturan bayar eksternal)', ''];
        } else {
            foreach ($ext as $p) {
                $rows[] = [
                    $p->term_name,
                    'Rp ' . number_format($p->price, 0, ',', '.') . ' / ' . ($freq[$p->frequency] ?? $p->frequency),
                ];
            }
        }

        return $rows;
    }
}
