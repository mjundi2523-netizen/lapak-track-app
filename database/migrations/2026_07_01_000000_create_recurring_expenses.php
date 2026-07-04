<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->bigIncrements('rxid');
            $table->unsignedBigInteger('ecid');
            $table->string('title');
            // Nominal acuan: dipakai langsung utk auto_post, sbg saran utk yg perlu konfirmasi.
            $table->unsignedBigInteger('amount');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'annual'])->default('monthly');
            $table->unsignedInteger('interval_count')->default(1);
            $table->enum('payment_method', ['tunai', 'transfer', 'lainnya'])->default('tunai');
            $table->date('start_date');
            // auto_post=true → langsung dicatat (posted); false → dibuat draft (pending) utk dikonfirmasi.
            $table->boolean('auto_post')->default(true);
            $table->boolean('is_active')->default(true);
            // Cursor generator: tanggal occurrence terakhir yang sudah dibuat (dekopel dari baris expenses).
            $table->date('generated_until')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('ecid')->references('ecid')->on('expense_categories');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('modified_by')->references('id')->on('users');
        });

        Schema::table('expenses', function (Blueprint $table) {
            // Occurrence hasil generate menempel ke templatenya (null = pengeluaran manual).
            $table->unsignedBigInteger('rxid')->nullable()->after('ecid');
            // posted = masuk hitungan; pending = draft menunggu konfirmasi nominal.
            $table->enum('status', ['posted', 'pending'])->default('posted')->after('note');

            $table->foreign('rxid')->references('rxid')->on('recurring_expenses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['rxid']);
            $table->dropColumn(['rxid', 'status']);
        });

        Schema::dropIfExists('recurring_expenses');
    }
};
