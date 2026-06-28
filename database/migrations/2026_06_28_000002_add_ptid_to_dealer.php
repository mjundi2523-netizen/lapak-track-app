<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer', function (Blueprint $table) {
            // Aturan bayar langsung untuk pedagang non-lapak (mis. eksternal),
            // yang tidak punya dealer_stall. Untuk regular/new tetap NULL (ambil dari lapak).
            $table->unsignedBigInteger('ptid')->nullable()->after('dealer_condition');
            $table->foreign('ptid')->references('ptid')->on('payment_terms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dealer', function (Blueprint $table) {
            $table->dropForeign(['ptid']);
            $table->dropColumn('ptid');
        });
    }
};
