<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| Penjadwalan generate tagihan & pengeluaran rutin.
| Dijalankan tengah malam WIB (timezone app = Asia/Jakarta) supaya tagihan yang
| jatuh tempo "hari ini" sudah muncul sejak 00:00 tanpa menunggu halaman dibuka.
| Catch-up lazy di mount() tetap dipertahankan sebagai jaring pengaman.
*/
Schedule::command('bills:generate')->dailyAt('00:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));

Schedule::command('expenses:generate')->dailyAt('00:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'));
