<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->index(['market_id', 'due_date', 'billing_status'], 'dealer_bills_market_due_status_index');
        });

        Schema::table('dealer_payment', function (Blueprint $table) {
            $table->index(['dbid', 'is_voided'], 'dealer_payment_dbid_voided_index');
        });
    }

    public function down(): void
    {
        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->dropIndex('dealer_bills_market_due_status_index');
        });

        Schema::table('dealer_payment', function (Blueprint $table) {
            $table->dropIndex('dealer_payment_dbid_voided_index');
        });
    }
};
