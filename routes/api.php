<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RenewalController;

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

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require valid token)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Statistics endpoint - must be defined BEFORE the resource route
    Route::get('renewals/statistics', [RenewalController::class, 'statistics']);
    
    // Get renewals by status
    Route::get('renewals/status/{status}', [RenewalController::class, 'getByStatus']);
    
    // Get current user's renewals
    Route::get('renewals/user', [RenewalController::class, 'getUserRenewals']);
    
    Route::get('user/profile', [RenewalController::class, 'getUserDetails']);

    // Renewal routes - using apiResource for proper RESTful API endpoints
    Route::apiResource('renewals', RenewalController::class);
});