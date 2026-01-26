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
    // Health check
    Route::get('/ping', [ExtensionController::class, 'ping']);

    // Import product (no auth required for simplicity - can add token auth later)
    Route::post('/import', [ExtensionController::class, 'import']);

    // Get product data for Etsy publishing
    Route::get('/product/{id}/etsy-data', [ExtensionController::class, 'getEtsyData']);
});
