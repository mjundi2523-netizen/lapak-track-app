<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_expenses', function (Blueprint $table) {
            // Batas akhir template (opsional). Null = berjalan tanpa batas.
            // Generator berhenti membuat occurrence yang tanggalnya melewati end_date.
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_expenses', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });
    }
};
