<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('add_ons', function (Blueprint $table) {
            // true  = penagihan mengikuti rent_start_date rental (ikut sewa).
            // false = penagihan mengikuti start_date sendiri (jadwal lepas).
            $table->boolean('is_rent_date')->default(true)->after('frequency');
            // Anchor jadwal saat is_rent_date = false. Diabaikan saat is_rent_date = true.
            $table->date('start_date')->nullable()->after('is_rent_date');
        });
    }

    public function down(): void
    {
        Schema::table('add_ons', function (Blueprint $table) {
            $table->dropColumn(['is_rent_date', 'start_date']);
        });
    }
};
