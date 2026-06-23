<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer', function (Blueprint $table) {
            $table->bigIncrements('did');
            $table->string('nik', 255)->unique();
            $table->string('name', 255);
            $table->date('birth_date');
            $table->string('address', 255);
            $table->string('phone_number_1', 255);
            $table->string('phone_number_2', 255)->nullable();
            $table->string('product_type', 255)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('scan_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer');
    }
};
