<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Template Excel untuk impor massal pedagang (kebutuhan migrasi).
 * Heading harus persis seperti di sini — di-snake_case-kan otomatis saat impor.
 * Dua baris contoh: satu pedagang biasa (menyewa lapak) & satu eksternal.
 */
class DealerImportTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Pedagang';
    }

    public function headings(): array
    {
        return [
            'NIK',
            'Nama',
            'Tanggal Lahir',
            'Alamat',
            'Telepon',
            'Telepon 2',
            'Jenis Dagangan',
            'No Surat',
            'Kondisi',        // regular | new | external (kosong = regular)
            'Lapak',          // kode lapak mis. A01/05 (untuk regular/new)
            'Mulai Sewa',     // wajib bila Lapak diisi (YYYY-MM-DD)
            'Akhir Sewa',     // opsional
            'Aturan Bayar',   // nama aturan bayar (wajib bila Kondisi = external)
            'Mulai Langganan',// wajib bila Kondisi = external (YYYY-MM-DD)
        ];
    }

    public function array(): array
    {
        return [
            ['3201010101010001', 'Budi Santoso', '1990-05-17', 'Jl. Melati No. 1', '081234567890', '', 'Sembako', '', 'regular', 'A01/05', '2026-01-01', '', '', ''],
            ['3202020202020002', 'Siti Aminah', '1985-11-02', 'Jl. Mawar No. 9', '081298765432', '', 'Sayur Keliling', '', 'external', '', '', '', 'Langganan Harian Eksternal', '2026-01-01'],
        ];
    }
}
