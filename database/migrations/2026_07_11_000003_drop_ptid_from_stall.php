<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aturan bayar lapak kini lewat pivot stall_payment_terms; ptid tunggal tak dipakai lagi.
        Schema::table('stall', function (Blueprint $table) {
            $table->dropForeign(['ptid']);
            $table->dropColumn('ptid');
        });
    }

    public function down(): void
    {
        Schema::table('stall', function (Blueprint $table) {
            $table->unsignedBigInteger('ptid')->nullable()->after('size');
            $table->foreign('ptid')->references('ptid')->on('payment_terms');
        });

        // Best-effort: pulihkan ptid tunggal dari pivot (ambil ptid terkecil bila lapak punya banyak).
        DB::statement('
            UPDATE stall s
            JOIN (SELECT sid, MIN(ptid) AS ptid FROM stall_payment_terms GROUP BY sid) x ON x.sid = s.sid
            SET s.ptid = x.ptid
        ');
    }
};
