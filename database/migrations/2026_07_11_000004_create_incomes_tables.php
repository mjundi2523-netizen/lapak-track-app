<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_categories', function (Blueprint $table) {
            $table->bigIncrements('icid');
            $table->unsignedBigInteger('market_id');
            $table->string('name');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('market_id')->references('mid')->on('markets')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
            $table->unique(['market_id', 'name'], 'income_categories_market_name_unique');
            $table->index('market_id');
        });

        Schema::create('incomes', function (Blueprint $table) {
            $table->bigIncrements('imid');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('icid');
            $table->string('title');
            $table->unsignedBigInteger('amount');
            $table->date('income_date');
            $table->enum('payment_method', ['tunai', 'transfer', 'lainnya'])->default('tunai');
            $table->text('note')->nullable();
            // Pola void (seperti expenses) — bukan edit/hard-delete.
            $table->boolean('is_voided')->default(false);
            $table->text('voided_reason')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('market_id')->references('mid')->on('markets')->cascadeOnDelete();
            $table->foreign('icid')->references('icid')->on('income_categories');
            $table->foreign('voided_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
            $table->index('market_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('income_categories');
    }
};
