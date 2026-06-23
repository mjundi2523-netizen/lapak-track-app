<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('add_ons', function (Blueprint $table) {
            $table->bigIncrements('aoid');
            $table->string('add_on', 255);
            $table->unsignedBigInteger('price');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'annual']);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('add_ons');
    }
};
