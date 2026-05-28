<?php

use App\Http\Controllers\User\Settings\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum']], function () {

    // User Settings

    Route::get('/my-settings', [SettingsController::class, 'getMySettings']);
    Route::get('/edit-my-email', [SettingsController::class, 'editEmailFormConfig']);
    Route::get('/edit-my-user-name', [SettingsController::class, 'editUserNameFormConfig']);
    Route::post('/update-my-email', [SettingsController::class, 'updateMyEmail']);
    Route::post('/update-my-user-name', [SettingsController::class, 'updateMyUserName']);
    Route::post('/update-password', [SettingsController::class, 'updatePassword']);

});
