<?php

use App\Http\Controllers\DashboardController;
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

    // Shop management routes (shops.index redirects to dashboard)
    Route::get('/shops', function () {
        return redirect()->route('dashboard');
    })->name('shops.index');
    Route::get('/shops/create', [ShopController::class, 'create'])->name('shops.create');
    Route::post('/shops', [ShopController::class, 'store'])->name('shops.store');
    Route::get('/shops/{shop}', [ShopController::class, 'edit'])->name('shops.edit');
    Route::put('/shops/{shop}', [ShopController::class, 'update'])->name('shops.update');
    Route::patch('/shops/{shop}', [ShopController::class, 'update']);
    Route::delete('/shops/{shop}', [ShopController::class, 'destroy'])->name('shops.destroy');
    Route::post('/shops/{shop}/switch', [ShopController::class, 'switch'])->name('shops.switch');
    Route::put('/shops/{shop}/categories', [ShopController::class, 'updateCategories'])->name('shops.update-categories');
    Route::put('/shops/{shop}/tags', [ShopController::class, 'updateTags'])->name('shops.update-tags');
    Route::post('/shops/{shop}/backgrounds', [ShopController::class, 'uploadBackground'])->name('shops.upload-background');
    Route::delete('/shops/{shop}/backgrounds', [ShopController::class, 'deleteBackground'])->name('shops.delete-background');
    Route::post('/shops/{shop}/specific-prompts', [ShopController::class, 'addSpecificPrompt'])->name('shops.add-specific-prompt');
    Route::delete('/shops/{shop}/specific-prompts', [ShopController::class, 'deleteSpecificPrompt'])->name('shops.delete-specific-prompt');

    // Product management routes
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::patch('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/analyze-aliexpress', [ProductController::class, 'analyzeAliExpress'])->name('products.analyze-aliexpress');
    Route::post('/products/analyze-printables', [ProductController::class, 'analyzePrintables'])->name('products.analyze-printables');
    Route::post('/products/optimize-content', [ProductController::class, 'optimizeContent'])->name('products.optimize-content');
    Route::post('/products/bulk-delete', [ProductController::class, 'bulkDelete'])->name('products.bulk-delete');
    Route::post('/products/{product}/generate-ai-images', [ProductController::class, 'generateAiImages'])->name('products.generate-ai-images');
    Route::post('/products/{product}/transform-single-image', [ProductController::class, 'transformSingleImage'])->name('products.transform-single-image');
    Route::post('/products/{product}/toggle-logo', [ProductController::class, 'toggleLogo'])->name('products.toggle-logo');
    Route::delete('/products/{product}/remove-real-image', [ProductController::class, 'removeRealImage'])->name('products.remove-real-image');
    Route::delete('/products/{product}/remove-image', [ProductController::class, 'removeImage'])->name('products.remove-image');
    Route::get('/products/{product}/download-images', [ProductController::class, 'downloadImages'])->name('products.download-images');
});

require __DIR__.'/auth.php';
