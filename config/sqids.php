<?php

// Konfigurasi obfuscation ID di URL (Sqids).
// PENTING: mengubah alphabet/min_length akan mengubah SEMUA URL yang beredar
// (bookmark/link lama jadi 404) — jangan diganti setelah production.
return [

    // Alphabet acak = "salt". Override via .env (SQIDS_ALPHABET) bila perlu
    // beda antar environment.
    'alphabet' => env('SQIDS_ALPHABET', 'k3G7QAe51FCsPW92uEOyq4Bg6Sp8YzVTmnU0liwDdHXLajZrfxNhobJIRcMvKt'),

    // Panjang minimum hasil encode agar URL tidak terlihat pendek/berurutan.
    'min_length' => (int) env('SQIDS_MIN_LENGTH', 10),

];
