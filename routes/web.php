<?php

use App\Http\Controllers\EtsyAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Shop management routes
    Route::resource('shops', ShopController::class);
    Route::post('/shops/{shop}/switch', [ShopController::class, 'switch'])->name('shops.switch');

    // Etsy OAuth routes
    Route::get('/etsy/connect/{shop}', [EtsyAuthController::class, 'connect'])->name('etsy.connect');
    Route::post('/shops/{shop}/etsy/disconnect', [EtsyAuthController::class, 'disconnect'])->name('etsy.disconnect');
});

// Etsy OAuth callback (no auth middleware as it's coming from Etsy)
Route::get('/etsy/callback', [EtsyAuthController::class, 'callback'])->name('etsy.callback');

require __DIR__.'/auth.php';
