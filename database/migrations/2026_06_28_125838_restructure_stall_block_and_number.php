<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Format lokasi baru: block = 1 huruf + 2 angka (mis. "A01"), number = 2 angka (mis. "05").
     * Satu lapak diidentifikasi unik oleh kombinasi (block, number).
     *
     * Konversi data lama "X-00N": tiap grup huruf jadi block "{huruf}01",
     * angka di belakang jadi number 2 digit. Contoh: A-001 → A01/01, A-006 → A01/06.
     */
    public function up(): void
    {
        // Tambah kolom + buang unique lama (block-saja) DULU, supaya backfill yang
        // memetakan banyak baris ke block sama (mis. "A01") tidak bentrok.
        Schema::table('stall', function (Blueprint $table) {
            $table->string('number', 2)->nullable()->after('block');
            $table->dropUnique('stall_block_unique');
        });

        // Backfill: pisah block lama (mis. "A-001") jadi block "{huruf}01" + number "0N".
        foreach (DB::table('stall')->get(['sid', 'block']) as $row) {
            $old = (string) $row->block;

            if (preg_match('/^([A-Za-z])\D*?(\d+)$/', $old, $m)) {
                $letter = strtoupper($m[1]);
                $num    = str_pad((string) ((int) $m[2]), 2, '0', STR_PAD_LEFT);
                $block  = $letter . '01';
            } else {
                // Tak terpola — taruh di block "Z01" agar tetap valid & unik.
                $block  = 'Z01';
                $num    = str_pad((string) $row->sid, 2, '0', STR_PAD_LEFT);
            }

            DB::table('stall')->where('sid', $row->sid)->update([
                'block'  => $block,
                'number' => $num,
            ]);
        }

        DB::statement('ALTER TABLE stall MODIFY number VARCHAR(2) NOT NULL');

        Schema::table('stall', function (Blueprint $table) {
            $table->unique(['block', 'number'], 'stall_block_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stall', function (Blueprint $table) {
            $table->dropUnique('stall_block_number_unique');
        });

        // Gabung kembali ke format lama "block-number" agar tetap unik.
        foreach (DB::table('stall')->get(['sid', 'block', 'number']) as $row) {
            DB::table('stall')->where('sid', $row->sid)->update([
                'block' => $row->block . '-' . $row->number,
            ]);
        }

        Schema::table('stall', function (Blueprint $table) {
            $table->unique('block', 'stall_block_unique');
            $table->dropColumn('number');
        });
    }
};
