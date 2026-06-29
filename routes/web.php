<?php

use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/', fn () => redirect('/dashboard'));
Route::get('/dashboard', App\Livewire\Dashboard::class)
    ->middleware(['auth', 'verified'])->name('dashboard');

// Profile (komponen Livewire full-page; ShowProfile sudah self-contained)
Route::middleware('auth')->group(function () {
    Route::get('/profile', App\Livewire\Profile\ShowProfile::class)->name('profile.edit');
});

// Livewire full-page routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Payment Terms
    Route::get('/aturan-bayar', App\Livewire\PaymentTerms\IndexPaymentTerms::class)->name('payment-terms.index');
    Route::get('/aturan-bayar/create', App\Livewire\PaymentTerms\CreatePaymentTerm::class)->name('payment-terms.create');
    Route::get('/aturan-bayar/{paymentTerm}/edit', App\Livewire\PaymentTerms\EditPaymentTerm::class)->name('payment-terms.edit');

    // Add-ons
    Route::get('/biaya-lain-lain', App\Livewire\AddOns\IndexAddOns::class)->name('add-ons.index');
    Route::get('/biaya-lain-lain/create', App\Livewire\AddOns\CreateAddOn::class)->name('add-ons.create');
    Route::get('/biaya-lain-lain/{addOn}/edit', App\Livewire\AddOns\EditAddOn::class)->name('add-ons.edit');

    // Stalls
    Route::get('/lapak', App\Livewire\Stalls\IndexStalls::class)->name('stalls.index');
    Route::get('/lapak/denah', App\Livewire\Stalls\StallMap::class)->middleware('premium')->name('stalls.map');
    Route::get('/lapak/create', App\Livewire\Stalls\CreateStall::class)->name('stalls.create');
    Route::get('/lapak/{stall}/edit', App\Livewire\Stalls\EditStall::class)->name('stalls.edit');
    Route::get('/lapak/{stall}', App\Livewire\Stalls\ShowStall::class)->name('stalls.show');

    // Dealers
    Route::get('/pedagang', App\Livewire\Dealers\IndexDealers::class)->name('dealers.index');
    Route::get('/pedagang/create', App\Livewire\Dealers\CreateDealer::class)->name('dealers.create');
    Route::get('/pedagang/{dealer}', App\Livewire\Dealers\ShowDealer::class)->name('dealers.show');
    Route::get('/pedagang/{dealer}/edit', App\Livewire\Dealers\EditDealer::class)->name('dealers.edit');

    // Bills
    Route::get('/tagihan', App\Livewire\Bills\IndexBills::class)->name('bills.index');
    Route::get('/tagihan-export', App\Http\Controllers\BillExportController::class)->name('bills.export');
    Route::get('/tagihan/{dealerBill}', App\Livewire\Bills\ShowBill::class)->name('bills.show');

    // Payments
    Route::get('/pembayaran', App\Livewire\Payments\IndexPayments::class)->name('payments.index');
    Route::get('/pembayaran/create', App\Livewire\Payments\CreatePayment::class)->name('payments.create');
    Route::get('/pembayaran/{payment}/void', App\Livewire\Payments\VoidPayment::class)->name('payments.void');
    Route::get('/pembayaran/{payment}', App\Livewire\Payments\ShowPayment::class)->name('payments.show');

    // Fitur premium: Kategori Pengeluaran, Pengeluaran, Laporan.
    Route::middleware('premium')->group(function () {
        // Kategori Pengeluaran (master)
        Route::get('/kategori-pengeluaran', App\Livewire\ExpenseCategories\IndexExpenseCategories::class)->name('expense-categories.index');
        Route::get('/kategori-pengeluaran/create', App\Livewire\ExpenseCategories\CreateExpenseCategory::class)->name('expense-categories.create');
        Route::get('/kategori-pengeluaran/{expenseCategory}/edit', App\Livewire\ExpenseCategories\EditExpenseCategory::class)->name('expense-categories.edit');

        // Pengeluaran
        Route::get('/pengeluaran', App\Livewire\Expenses\IndexExpenses::class)->name('expenses.index');
        Route::get('/pengeluaran/create', App\Livewire\Expenses\CreateExpense::class)->name('expenses.create');
        Route::get('/pengeluaran/{expense}/void', App\Livewire\Expenses\VoidExpense::class)->name('expenses.void');

        // Laporan
        Route::get('/laporan/arus-kas', App\Livewire\Reports\CashFlow::class)->name('reports.cash-flow');
        Route::get('/laporan/rekap-penerimaan', App\Livewire\Reports\Collection::class)->name('reports.collection');
        Route::get('/laporan/rekap-penerimaan-export', App\Http\Controllers\CollectionExportController::class)->name('reports.collection.export');
        Route::get('/laporan/rekap-pedagang', App\Livewire\Reports\DealerSummary::class)->name('reports.dealer-summary');
        Route::get('/laporan/rekap-pedagang-export', App\Http\Controllers\DealerSummaryExportController::class)->name('reports.dealer-summary.export');
    });
});

require __DIR__.'/auth.php';
