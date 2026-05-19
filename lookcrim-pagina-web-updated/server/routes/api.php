<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RegistersController;
use App\Http\Controllers\Api\V1\MetaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::get('/register-categories', [MetaController::class, 'registerCategories']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/me', [AuthController::class, 'updateMe']);
        Route::post('/me/email-change', [AuthController::class, 'requestEmailChange']);
        Route::put('/me/password', [AuthController::class, 'updatePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/registers', [RegistersController::class, 'index']);
        Route::post('/registers', [RegistersController::class, 'store']);
        Route::get('/registers/{id}', [RegistersController::class, 'show'])->where('id', '[0-9]+');

        // PHP file uploads work reliably with POST multipart; accept POST for updates as well.
        Route::post('/registers/{id}', [RegistersController::class, 'update'])->where('id', '[0-9]+');
        Route::match(['put', 'patch'], '/registers/{id}', [RegistersController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('/registers/{id}', [RegistersController::class, 'destroy'])->where('id', '[0-9]+');
    });
});