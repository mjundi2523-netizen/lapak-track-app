<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE dealer_bills MODIFY billing_status ENUM('paid','installment','unpaid','pending','cancelled') NOT NULL DEFAULT 'unpaid'");
    }

    public function down(): void
    {
        // Kembalikan baris 'cancelled' agar enum bisa diciutkan lagi.
        DB::table('dealer_bills')->where('billing_status', 'cancelled')->update(['billing_status' => 'unpaid']);
        DB::statement("ALTER TABLE dealer_bills MODIFY billing_status ENUM('paid','installment','unpaid','pending') NOT NULL DEFAULT 'unpaid'");
    }
};
