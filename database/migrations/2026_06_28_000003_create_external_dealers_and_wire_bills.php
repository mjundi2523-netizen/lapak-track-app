<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Langganan" pedagang eksternal — sejajar dealer_stall, tapi relasi ke payment_terms.
        Schema::create('external_dealers', function (Blueprint $table) {
            $table->bigIncrements('edid');
            $table->unsignedBigInteger('did');
            $table->unsignedBigInteger('ptid');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('deleted')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('did')->references('did')->on('dealer');
            $table->foreign('ptid')->references('ptid')->on('payment_terms');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });

        // dealer_bills: parent alternatif edid + dsid jadi nullable + tipe EXT.
        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->unsignedBigInteger('edid')->nullable()->after('dsid');
            $table->foreign('edid')->references('edid')->on('external_dealers')->nullOnDelete();
        });

        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->dropForeign(['dsid']);
        });
        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->unsignedBigInteger('dsid')->nullable()->change();
            $table->foreign('dsid')->references('dsid')->on('dealer_stall');
        });

        DB::statement("ALTER TABLE dealer_bills MODIFY bill_type ENUM('MTR','MAT','AAT','ATR','EXT') NOT NULL DEFAULT 'MTR'");

        // dealer.ptid (sementara) digantikan external_dealers.ptid.
        Schema::table('dealer', function (Blueprint $table) {
            $table->dropForeign(['ptid']);
            $table->dropColumn('ptid');
        });
    }

    public function down(): void
    {
        Schema::table('dealer', function (Blueprint $table) {
            $table->unsignedBigInteger('ptid')->nullable()->after('dealer_condition');
            $table->foreign('ptid')->references('ptid')->on('payment_terms')->nullOnDelete();
        });

        DB::table('dealer_bills')->where('bill_type', 'EXT')->update(['bill_type' => 'MTR']);
        DB::statement("ALTER TABLE dealer_bills MODIFY bill_type ENUM('MTR','MAT','AAT','ATR') NOT NULL DEFAULT 'MTR'");

        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->dropForeign(['edid']);
            $table->dropColumn('edid');
        });
        // dsid kembali NOT NULL (asumsi tak ada bill eksternal tersisa).
        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->dropForeign(['dsid']);
        });
        Schema::table('dealer_bills', function (Blueprint $table) {
            $table->unsignedBigInteger('dsid')->nullable(false)->change();
            $table->foreign('dsid')->references('dsid')->on('dealer_stall');
        });

        Schema::dropIfExists('external_dealers');
    }
};
