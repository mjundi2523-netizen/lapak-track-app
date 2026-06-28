<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer', function (Blueprint $table) {
            // Pedagang baru → memakai aturan bayar (payment_terms) khusus is_new.
            $table->boolean('is_new')->default(false)->after('status');
        });

        Schema::table('payment_terms', function (Blueprint $table) {
            // Aturan bayar khusus untuk pedagang baru.
            $table->boolean('is_new')->default(false)->after('interval_count');
        });
    }

    public function down(): void
    {
        Schema::table('dealer', function (Blueprint $table) {
            $table->dropColumn('is_new');
        });

        Schema::table('payment_terms', function (Blueprint $table) {
            $table->dropColumn('is_new');
        });
    }
};
