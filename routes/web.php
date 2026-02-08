<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

// Serve storage files without symlink (for Laravel Cloud)
Route::get('/storage/{path}', function (string $path) {
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return response()->file(Storage::disk('public')->path($path));
})->where('path', '.*')->name('storage.serve');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/api-keys', [ProfileController::class, 'updateApiKeys'])->name('profile.update-api-keys');
    Route::post('/profile/tokens', [ProfileController::class, 'createToken'])->name('profile.create-token');
    Route::delete('/profile/tokens/{token}', [ProfileController::class, 'revokeToken'])->name('profile.revoke-token');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Shop management routes
    Route::resource('shops', ShopController::class);
    Route::post('/shops/{shop}/switch', [ShopController::class, 'switch'])->name('shops.switch');
    Route::put('/shops/{shop}/categories', [ShopController::class, 'updateCategories'])->name('shops.update-categories');
    Route::put('/shops/{shop}/tags', [ShopController::class, 'updateTags'])->name('shops.update-tags');
    Route::post('/shops/{shop}/backgrounds', [ShopController::class, 'uploadBackground'])->name('shops.upload-background');
    Route::delete('/shops/{shop}/backgrounds', [ShopController::class, 'deleteBackground'])->name('shops.delete-background');

    // Product management routes
    Route::resource('products', ProductController::class);
    Route::post('/products/analyze-aliexpress', [ProductController::class, 'analyzeAliExpress'])->name('products.analyze-aliexpress');
    Route::post('/products/analyze-printables', [ProductController::class, 'analyzePrintables'])->name('products.analyze-printables');
    Route::post('/products/optimize-content', [ProductController::class, 'optimizeContent'])->name('products.optimize-content');
    Route::post('/products/bulk-delete', [ProductController::class, 'bulkDelete'])->name('products.bulk-delete');
    Route::post('/products/{product}/generate-ai-images', [ProductController::class, 'generateAiImages'])->name('products.generate-ai-images');
    Route::post('/products/{product}/transform-single-image', [ProductController::class, 'transformSingleImage'])->name('products.transform-single-image');
    Route::delete('/products/{product}/remove-real-image', [ProductController::class, 'removeRealImage'])->name('products.remove-real-image');
    Route::delete('/products/{product}/remove-image', [ProductController::class, 'removeImage'])->name('products.remove-image');

    // Order management routes
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('/orders/{order}/note', [OrderController::class, 'addNote'])->name('orders.add-note');
});

require __DIR__.'/auth.php';
