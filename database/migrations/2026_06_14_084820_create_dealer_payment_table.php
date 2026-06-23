<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_payment', function (Blueprint $table) {
            $table->bigIncrements('dpid');
            $table->unsignedBigInteger('dbid');
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->date('payment_date');
            $table->enum('payment_method', ['tunai', 'transfer', 'lainnya']);
            $table->boolean('is_voided')->default(false);
            $table->text('voided_reason')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('dbid')->references('dbid')->on('dealer_bills');
            $table->foreign('voided_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_payment');
    }
};
