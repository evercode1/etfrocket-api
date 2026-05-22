<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\BackTesting\BackTestingController;


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
