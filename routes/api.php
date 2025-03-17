<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;

Route::get('/', function () {
    return response()->json(['message' => 'Сервер работает!'], 200);
});
Route::prefix('auth')->group(function   () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-phone', [AuthController::class, 'verifyPhone']);
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/verify-login-code', [AuthController::class, 'verifyLoginCode']);
    Route::post('/start-password-recovery', [AuthController::class, 'startPasswordRecovery']);
    Route::post('/verify-recovery-code', [AuthController::class, 'verifyRecoveryCode']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/resend-code', [AuthController::class, 'resendCode']);
});

Route::middleware('auth:sanctum')->get('/check-auth', [AuthController::class, 'checkAuth']);
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'getUser']);

    

Route::prefix('orders')->middleware('auth:sanctum')->group(function () 
    {
        Route::post('/', [OrderController::class, 'createOrder']);
        Route::get('/client', [OrderController::class, 'getUserOrders']);
        Route::put('/{order}/cancel', [OrderController::class, 'cancelOrder']);
        Route::put('/{order}/accept', [OrderController::class, 'acceptOrder']);
        Route::put('/{order}/complete', [OrderController::class, 'completeOrder']);
        Route::get('/client/completed', [OrderController::class, 'getCompletedOrders']);
        Route::get('/client/statuses', [OrderController::class, 'getUserOrderStatuses']);
        Route::get('/towtruck', [OrderController::class, 'getOrders']);
        Route::get('/accepted', [OrderController::class, 'getAcceptedOrders']);
        Route::get('completed', [OrderController::class, 'getTowTruckCompletedOrders']);
    });

Route::get('/test-session', function () {
    session(['key' => 'value']);
    Log::info('Current session data:', session()->all());
    return session('key');
});
Route::get('/debug', function () {
    return response()->json(['status' => 'debug route works']);
});
