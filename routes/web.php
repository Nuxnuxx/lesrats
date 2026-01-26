<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EtsyAuthController;
use App\Http\Controllers\EtsyMockController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Onboarding routes
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding/connect-etsy', [OnboardingController::class, 'connectEtsy'])->name('onboarding.connect-etsy');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/api-keys', [ProfileController::class, 'updateApiKeys'])->name('profile.update-api-keys');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/connect-etsy', [ProfileController::class, 'connectEtsy'])->name('profile.connect-etsy');

    // Shop management routes
    Route::resource('shops', ShopController::class);
    Route::post('/shops/{shop}/switch', [ShopController::class, 'switch'])->name('shops.switch');

    // Etsy OAuth routes
    Route::get('/etsy/connect/{shop}', [EtsyAuthController::class, 'connect'])->name('etsy.connect');
    Route::post('/shops/{shop}/etsy/disconnect', [EtsyAuthController::class, 'disconnect'])->name('etsy.disconnect');

    // Product management routes
    Route::resource('products', ProductController::class);
    Route::post('/products/{product}/sync-etsy', [ProductController::class, 'syncToEtsy'])->name('products.sync-etsy');
    Route::post('/products/import-etsy', [ProductController::class, 'importFromEtsy'])->name('products.import-etsy');
    Route::post('/products/analyze-aliexpress', [ProductController::class, 'analyzeAliExpress'])->name('products.analyze-aliexpress');
    Route::post('/products/analyze-printables', [ProductController::class, 'analyzePrintables'])->name('products.analyze-printables');
    Route::post('/products/optimize-content', [ProductController::class, 'optimizeContent'])->name('products.optimize-content');
    Route::post('/products/bulk-sync', [ProductController::class, 'bulkSync'])->name('products.bulk-sync');
    Route::post('/products/bulk-delete', [ProductController::class, 'bulkDelete'])->name('products.bulk-delete');
    Route::post('/products/{product}/generate-ai-images', [ProductController::class, 'generateAiImages'])->name('products.generate-ai-images');
    Route::post('/products/export-etsy', [ProductController::class, 'exportForEtsy'])->name('products.export-etsy');

    // Order management routes
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('/orders/{order}/note', [OrderController::class, 'addNote'])->name('orders.add-note');
    Route::post('/orders/import-etsy', [OrderController::class, 'importFromEtsy'])->name('orders.import-etsy');
});

// Etsy OAuth callback (no auth middleware as it's coming from Etsy)
Route::get('/etsy/callback', [EtsyAuthController::class, 'callback'])->name('etsy.callback');

// Etsy Mock routes (only available when ETSY_MOCK_ENABLED=true)
Route::get('/etsy/mock/authorize', [EtsyMockController::class, 'authorize'])->name('etsy.mock.authorize');
Route::post('/etsy/mock/approve', [EtsyMockController::class, 'approve'])->name('etsy.mock.approve');
Route::post('/etsy/mock/deny', [EtsyMockController::class, 'deny'])->name('etsy.mock.deny');

require __DIR__.'/auth.php';
