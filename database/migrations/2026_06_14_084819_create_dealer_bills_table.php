<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_bills', function (Blueprint $table) {
            $table->bigIncrements('dbid');
            $table->unsignedBigInteger('dsid');
            $table->unsignedBigInteger('total_amount');
            $table->date('due_date');
            $table->enum('billing_status', ['paid', 'installment', 'unpaid', 'pending'])->default('unpaid');
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('dsid')->references('dsid')->on('dealer_stall');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_bills');
    }
};
