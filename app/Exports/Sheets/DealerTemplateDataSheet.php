<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Sheet data (yang diisi & dibaca importer). Sengaja HANYA baris judul —
 * tanpa contoh — supaya tak ada baris contoh yang keimpor tak sengaja.
 * Contoh & penjelasan ada di sheet "Keterangan".
 */
class DealerTemplateDataSheet implements FromArray, WithHeadings, WithTitle
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
            'Tanggal Mulai',  // mulai sewa (regular/new) ATAU mulai langganan (external)
            'Akhir Sewa',     // opsional, hanya penyewa lapak
            'Aturan Bayar',   // external: nama term eksternal; regular/new: nama term lapak bila lapak punya >1
        ];
    }

    public function array(): array
    {
        return []; // isi mulai baris ke-2
    }
}
