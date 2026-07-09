<?php

namespace App\Exports;

use App\Exports\Sheets\DealerTemplateDataSheet;
use App\Exports\Sheets\DealerTemplateLegendSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Template Excel untuk impor massal pedagang (kebutuhan migrasi).
 * Dua sheet:
 *  - "Pedagang"   → tempat mengisi data (dibaca importer; hanya baris judul).
 *  - "Keterangan" → panduan kolom + daftar pilihan nyata (kondisi, lapak, aturan bayar).
 */
class DealerImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new DealerTemplateDataSheet,
            new DealerTemplateLegendSheet,
        ];
    }
}
