<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms_add_ons', function (Blueprint $table) {
            $table->bigIncrements('taoid');
            $table->unsignedBigInteger('ptid');
            $table->unsignedBigInteger('aoid');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('ptid')->references('ptid')->on('payment_terms');
            $table->foreign('aoid')->references('aoid')->on('add_ons');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_add_ons');
    }
};
