<?php
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
 
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');
    
    // Rutas de verificación de email
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])->middleware('auth:api')->name('verification.resend');
    
    // Rutas de recuperación de contraseña
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');
});