<?php
// File: routes/api.php - ADD these routes

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ✅ API Routes untuk Medical Record System
Route::middleware(['auth:web'])->prefix('users')->group(function () {
    // Get medical record number
    Route::get('/{userId}/medical-record', [UserController::class, 'getMedicalRecord'])
        ->name('api.users.medical-record');
    
    // Get complete user details
    Route::get('/{userId}/details', [UserController::class, 'getDetails'])
        ->name('api.users.details');
    
    // Search users for medical record
    Route::get('/search/medical-record', [UserController::class, 'searchForMedicalRecord'])
        ->name('api.users.search-medical-record');
});

// ✅ Alternative routes for dokter panel (if needed different auth)
Route::middleware(['auth:dokter'])->prefix('dokter/users')->group(function () {
    Route::get('/{userId}/medical-record', [UserController::class, 'getMedicalRecord']);
    Route::get('/{userId}/details', [UserController::class, 'getDetails']);
    Route::get('/search/medical-record', [UserController::class, 'searchForMedicalRecord']);
});