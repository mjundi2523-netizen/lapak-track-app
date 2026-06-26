<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer_bills', function (Blueprint $table) {
            // MTR = sewa pokok, ATR = biaya lain-lain (add-on). Diskriminator stream billing.
            $table->enum('bill_type', ['MTR', 'ATR'])->default('MTR')->after('bill_id');
            // Frekuensi yang diwakili tagihan ini (untuk cursor roll-forward & tampilan).
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'annual'])->nullable()->after('bill_type');
        });

        // Backfill bill_type dari prefix bill_id.
        DB::table('dealer_bills')->where('bill_id', 'like', 'ATR-%')->update(['bill_type' => 'ATR']);
        DB::table('dealer_bills')->where('bill_id', 'like', 'MTR-%')->update(['bill_type' => 'MTR']);

        // Backfill frequency dari rentang periode (data lama: interval_count = 1 satuan).
        foreach (DB::table('dealer_bills')->get() as $bill) {
            $days = Carbon::parse($bill->period_start)->diffInDays(Carbon::parse($bill->period_end));

            $frequency = match (true) {
                $days <= 1 => 'daily',
                $days <= 7 => 'weekly',
                $days <= 31 => 'monthly',
                default => 'annual',
            };

            DB::table('dealer_bills')->where('dbid', $bill->dbid)->update(['frequency' => $frequency]);
        }
    }

    public function down(): void
    {
        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->dropColumn(['bill_type', 'frequency']);
        });
    }
};
