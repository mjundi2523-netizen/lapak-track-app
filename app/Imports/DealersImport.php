<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Penanda impor pedagang: cukup WithHeadingRow supaya baris pertama Excel
 * dipakai sebagai header dan tiap baris jadi array asosiatif ber-key snake_case
 * (mis. "Tanggal Lahir" → tanggal_lahir). Pemrosesan/validasi baris ditangani
 * di CreateDealer::importExcel() agar bisa transaksional + lapor error per-baris.
 */
class DealersImport implements WithHeadingRow {}
