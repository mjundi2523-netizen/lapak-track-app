<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->string('bill_id', 20)->unique()->nullable()->after('dbid');
        });

        Schema::table('dealer_payment', function (Blueprint $table) {
            $table->string('bill_id', 20)->unique()->nullable()->after('dpid');
        });
    }

    public function down(): void
    {
        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->dropUnique(['bill_id']);
            $table->dropColumn('bill_id');
        });

        Schema::table('dealer_payment', function (Blueprint $table) {
            $table->dropUnique(['bill_id']);
            $table->dropColumn('bill_id');
        });
    }
};
