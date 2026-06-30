<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('terms_add_ons');

        Schema::create('stall_add_ons', function (Blueprint $table) {
            $table->bigIncrements('saoid');
            $table->unsignedBigInteger('sid');
            $table->unsignedBigInteger('aoid');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            // Cegah duplikat pasangan lapak–add-on (pengaman; sesuai DB produksi).
            $table->unique(['sid', 'aoid'], 'stall_add_ons_sid_aoid_unique');

            $table->foreign('sid')->references('sid')->on('stall');
            $table->foreign('aoid')->references('aoid')->on('add_ons');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stall_add_ons');

        Schema::create('terms_add_ons', function (Blueprint $table) {
            $table->bigIncrements('taoid');
            $table->unsignedBigInteger('ptid');
            $table->unsignedBigInteger('aoid');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();
        });
    }
};
