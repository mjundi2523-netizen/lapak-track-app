<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->bigIncrements('ecid');
            $table->string('name')->unique();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->bigIncrements('xpid');
            $table->unsignedBigInteger('ecid');
            $table->string('title');
            $table->unsignedBigInteger('amount');
            $table->date('expense_date');
            $table->enum('payment_method', ['tunai', 'transfer', 'lainnya'])->default('tunai');
            $table->text('note')->nullable();
            // Koreksi pakai pola void (seperti dealer_payment) — bukan edit/hard-delete.
            $table->boolean('is_voided')->default(false);
            $table->text('voided_reason')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('ecid')->references('ecid')->on('expense_categories');
            $table->foreign('voided_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
