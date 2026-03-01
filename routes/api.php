<?php

use App\Http\Controllers\Api\ExtensionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Extension Browser Routes
Route::prefix('extension')->group(function () {
    // Health check (public - allows extension to verify API connectivity)
    Route::get('/ping', [ExtensionController::class, 'ping']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Get list of shops (only user's shops)
        Route::get('/shops', [ExtensionController::class, 'getShops']);

        // Import product
        Route::post('/import', [ExtensionController::class, 'import']);

        // Get product data for Etsy publishing
        Route::get('/product/{id}/etsy-data', [ExtensionController::class, 'getEtsyData']);

        // AI shop suggestion based on product data
        Route::post('/suggest-shop', [ExtensionController::class, 'suggestShop']);
    });
});
