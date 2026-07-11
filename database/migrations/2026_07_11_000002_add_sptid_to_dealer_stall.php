<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer_stall', function (Blueprint $table) {
            $table->unsignedBigInteger('sptid')->nullable()->after('sid');
            $table->foreign('sptid')->references('sptid')->on('stall_payment_terms');
        });

        // Backfill: sptid ← baris pivot dgn sid sama & ptid = ptid lama lapak.
        // Wajib dijalankan SEBELUM migrasi drop_ptid_from_stall (masih baca stall.ptid).
        DB::statement('
            UPDATE dealer_stall ds
            JOIN stall s ON s.sid = ds.sid
            JOIN stall_payment_terms spt ON spt.sid = ds.sid AND spt.ptid = s.ptid
            SET ds.sptid = spt.sptid
        ');
    }

    public function down(): void
    {
        Schema::table('dealer_stall', function (Blueprint $table) {
            $table->dropForeign(['sptid']);
            $table->dropColumn('sptid');
        });
    }
};
