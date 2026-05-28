<?php

use App\Http\Controllers\User\Auth\AuthController;
use App\Http\Controllers\User\Auth\PasswordResetController;
use App\Http\Controllers\User\Auth\UserVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Account Verification

Route::get('/account/verify/{token}', [UserVerificationController::class, 'verifyAccount']);
Route::get('/request-verification-token', [UserVerificationController::class, 'requestVerificationToken']);

// Auth

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/login-check', [AuthController::class, 'loginCheck']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {

    return response()->json([
        'success' => true,
        'data' => $request->user(),
    ]);
});

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::get('/permission-denied', [AuthController::class, 'permissionDenied']);

// Password Resets

Route::get('/get-password-reset-form/{token}', [PasswordResetController::class, 'getPasswordResetForm']);
Route::post('/password-reset', [PasswordResetController::class, 'passwordReset']);
Route::post('/request-password-token', [PasswordResetController::class, 'requestPasswordResetToken']);
