<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stall', function (Blueprint $table) {
            $table->bigIncrements('sid');
            $table->string('block', 255)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('ptid')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('ptid')->references('ptid')->on('payment_terms');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stall');
    }
};
