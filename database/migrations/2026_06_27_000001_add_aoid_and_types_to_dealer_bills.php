<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Perluas enum tipe tagihan:
        //  MTR = sewa saja
        //  MAT = sewa + add-on(is_rent_date=true) frekuensi sama (digabung)
        //  AAT = add-on(is_rent_date=true) saja, frekuensi != sewa (digabung per frekuensi)
        //  ATR = add-on(is_rent_date=false), per-add-on (jadwal start_date sendiri)
        DB::statement("ALTER TABLE dealer_bills MODIFY bill_type ENUM('MTR','MAT','AAT','ATR') NOT NULL DEFAULT 'MTR'");

        Schema::table('dealer_bills', function (Blueprint $table) {
            // Hanya terisi untuk ATR (stream per-add-on). MTR/MAT/AAT = null (stream gabungan).
            $table->unsignedBigInteger('aoid')->nullable()->after('frequency');
            $table->foreign('aoid')->references('aoid')->on('add_ons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->dropForeign(['aoid']);
            $table->dropColumn('aoid');
        });

        DB::statement("ALTER TABLE dealer_bills MODIFY bill_type ENUM('MTR','ATR') NOT NULL DEFAULT 'MTR'");
    }
};
