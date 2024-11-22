<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return response()->json(['message' => 'Сервер работает!']);
});
Route::middleware(['auth:api', 'role:client'])->group(function () {
    Route::post('/orders', [OrderController::class, 'createOrder']);
    Route::get('/orders/client', [OrderController::class, 'getUserOrders']);
    Route::get('/orders/client/statuses', [OrderController::class, 'getUserOrderStatuses']);
});
Route::middleware(['auth:api', 'role:towtruck'])->group(function () {
    Route::get('/orders/towtruck', [OrderController::class, 'getOrders']);
});

Route::post('/start-password-recovery', [AuthController::class, 'startPasswordRecovery']);
Route::post('/verify-recovery-code', [AuthController::class, 'verifyRecoveryCode']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/resend-code', [AuthController::class, 'resendCode']);
Route::post('/verify-phone', [AuthController::class, 'verifyPhone']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-login-code', [AuthController::class, 'verifyLoginCode']);
Route::get('/csrf-token', function () {
    return response()->json([
        'csrfToken' => csrf_token(),
        'sessionId' => session()->getId(),
        'sessionData' => session()->all()
    ]);
});