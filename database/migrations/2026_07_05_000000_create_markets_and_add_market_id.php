<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tabel domain yang di-scope per market (dapat kolom market_id NOT NULL + FK). */
    private array $tables = [
        'payment_terms', 'add_ons', 'stall', 'dealer', 'dealer_stall',
        'external_dealers', 'dealer_bills', 'dealer_payment',
        'expense_categories', 'expenses', 'recurring_expenses', 'stall_add_ons',
    ];

    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->bigIncrements('mid');
            $table->string('name');
            $table->string('owner_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Market default untuk menampung seluruh data lama (data existing satu tenant).
        $defaultId = DB::table('markets')->insertGetId([
            'name' => 'Pasar Default',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // users: market_id (nullable — developer/superadmin boleh null) + gerbang approval.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('market_id')->nullable()->after('id');
            $table->boolean('is_approved')->default(false)->after('is_premium');
        });
        // User lama = sudah aktif & masuk market default.
        DB::table('users')->update(['market_id' => $defaultId, 'is_approved' => true]);
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('market_id')->references('mid')->on('markets')->nullOnDelete();
        });

        // Semua tabel domain: tambah market_id, backfill ke default, jadikan NOT NULL + FK + index.
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->unsignedBigInteger('market_id')->nullable();
            });
            DB::table($t)->update(['market_id' => $defaultId]);
            Schema::table($t, function (Blueprint $table) {
                $table->unsignedBigInteger('market_id')->nullable(false)->change();
                $table->foreign('market_id')->references('mid')->on('markets');
                $table->index('market_id');
            });
        }

        // Unique yang jadi PER-MARKET (bukan global). Tanpa ini, market B tak bisa punya
        // lapak "A01/05", kategori "Listrik", NIK, atau nama aturan bayar yang sama dgn market A.
        Schema::table('stall', function (Blueprint $table) {
            $table->dropUnique('stall_block_number_unique');
            $table->unique(['market_id', 'block', 'number'], 'stall_market_block_number_unique');
        });
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropUnique('expense_categories_name_unique');
            $table->unique(['market_id', 'name'], 'expense_categories_market_name_unique');
        });
        Schema::table('dealer', function (Blueprint $table) {
            $table->dropUnique('dealer_nik_unique');
            $table->unique(['market_id', 'nik'], 'dealer_market_nik_unique');
        });
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->dropUnique('payment_terms_term_name_unique');
            $table->unique(['market_id', 'term_name'], 'payment_terms_market_term_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stall', function (Blueprint $table) {
            $table->dropUnique('stall_market_block_number_unique');
            $table->unique(['block', 'number'], 'stall_block_number_unique');
        });
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropUnique('expense_categories_market_name_unique');
            $table->unique('name', 'expense_categories_name_unique');
        });
        Schema::table('dealer', function (Blueprint $table) {
            $table->dropUnique('dealer_market_nik_unique');
            $table->unique('nik', 'dealer_nik_unique');
        });
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->dropUnique('payment_terms_market_term_name_unique');
            $table->unique('term_name', 'payment_terms_term_name_unique');
        });

        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropForeign(['market_id']);
                $table->dropColumn('market_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['market_id']);
            $table->dropColumn(['market_id', 'is_approved']);
        });

        Schema::dropIfExists('markets');
    }
};
