<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_stall', function (Blueprint $table) {
            $table->bigIncrements('dsid');
            $table->unsignedBigInteger('did');
            $table->unsignedBigInteger('sid');
            $table->date('rent_start_date');
            $table->date('rent_end_date')->nullable();
            $table->boolean('deleted')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('did')->references('did')->on('dealer');
            $table->foreign('sid')->references('sid')->on('stall');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_stall');
    }
};
