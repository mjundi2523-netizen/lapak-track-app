<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stall_payment_terms', function (Blueprint $table) {
            $table->bigIncrements('sptid');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('sid');
            $table->unsignedBigInteger('ptid');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            // Cegah duplikat pasangan lapak–aturan bayar (pengaman; sejajar stall_add_ons).
            $table->unique(['sid', 'ptid'], 'stall_payment_terms_sid_ptid_unique');

            $table->foreign('market_id')->references('mid')->on('markets');
            $table->foreign('sid')->references('sid')->on('stall');
            $table->foreign('ptid')->references('ptid')->on('payment_terms');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
            $table->index('market_id');
        });

        // Backfill: tiap lapak yang punya ptid tunggal → 1 baris pivot (bawa market_id & created_by lapak).
        $now = now();
        foreach (DB::table('stall')->whereNotNull('ptid')->get(['sid', 'ptid', 'market_id', 'created_by']) as $s) {
            DB::table('stall_payment_terms')->insert([
                'market_id' => $s->market_id,
                'sid' => $s->sid,
                'ptid' => $s->ptid,
                'created_by' => $s->created_by ?? 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stall_payment_terms');
    }
};
