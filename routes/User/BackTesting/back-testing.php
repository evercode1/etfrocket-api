<?php

use App\Http\Controllers\User\BackTesting\BackTestingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User BackTesting ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/back-testing', [BackTestingController::class, 'index']);
});
