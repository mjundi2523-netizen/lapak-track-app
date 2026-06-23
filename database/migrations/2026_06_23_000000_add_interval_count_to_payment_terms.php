<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_terms', function (Blueprint $table) {
            // "setiap {interval_count} {frequency}" — mis. monthly + 3 = tiap 3 bulan.
            // Default 1 → data lama tetap valid ("tiap 1 periode").
            $table->unsignedInteger('interval_count')->default(1)->after('frequency');
        });
    }

    public function down(): void
    {
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->dropColumn('interval_count');
        });
    }
};
