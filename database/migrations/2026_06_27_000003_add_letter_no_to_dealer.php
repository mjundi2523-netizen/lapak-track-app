<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer', function (Blueprint $table) {
            // Nomor surat/kartu pedagang — diisi bebas oleh user per pedagang.
            $table->string('letter_no', 100)->nullable()->after('scan_id');
        });
    }

    public function down(): void
    {
        Schema::table('dealer', function (Blueprint $table) {
            $table->dropColumn('letter_no');
        });
    }
};
