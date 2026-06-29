<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kontak Developer (untuk modal fitur premium)
    |--------------------------------------------------------------------------
    | Nomor WhatsApp dalam format internasional tanpa "+" atau spasi,
    | mis. 6281234567890. Diisi lewat .env: DEVELOPER_WHATSAPP=...
    */
    'developer_whatsapp' => env('DEVELOPER_WHATSAPP', '6282123061202'),

    'developer_name' => env('DEVELOPER_NAME', 'Developer LapakTrack'),

    // Pesan default yang sudah terisi saat membuka chat WA.
    'premium_wa_message' => env(
        'PREMIUM_WA_MESSAGE',
        'Halo, saya tertarik mengaktifkan fitur premium LapakTrack (laporan, denah lapak, pengeluaran, pedagang eksternal). Mohon informasinya.'
    ),
];
