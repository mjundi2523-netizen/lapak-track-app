<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // dealer: is_new (bool) -> dealer_condition (enum)
        Schema::table('dealer', function (Blueprint $table) {
            $table->enum('dealer_condition', ['regular', 'new', 'external'])->default('regular')->after('status');
        });
        DB::table('dealer')->where('is_new', true)->update(['dealer_condition' => 'new']);
        Schema::table('dealer', function (Blueprint $table) {
            $table->dropColumn('is_new');
        });

        // payment_terms: is_new (bool) -> dealer_condition (enum)
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->enum('dealer_condition', ['regular', 'new', 'external'])->default('regular')->after('interval_count');
        });
        DB::table('payment_terms')->where('is_new', true)->update(['dealer_condition' => 'new']);
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->dropColumn('is_new');
        });
    }

    public function down(): void
    {
        Schema::table('dealer', function (Blueprint $table) {
            $table->boolean('is_new')->default(false)->after('status');
        });
        DB::table('dealer')->where('dealer_condition', 'new')->update(['is_new' => true]);
        Schema::table('dealer', function (Blueprint $table) {
            $table->dropColumn('dealer_condition');
        });

        Schema::table('payment_terms', function (Blueprint $table) {
            $table->boolean('is_new')->default(false)->after('interval_count');
        });
        DB::table('payment_terms')->where('dealer_condition', 'new')->update(['is_new' => true]);
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->dropColumn('dealer_condition');
        });
    }
};
