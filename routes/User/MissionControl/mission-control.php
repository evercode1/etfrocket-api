<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\User\MissionControl\MissionControlController;


/*
|--------------------------------------------------------------------------
| User Mission Control ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum']], function () {


    Route::get('/get-portfolio-selects', [MissionControlController::class, 'getPortfolioSelects']);
    Route::get('/mission-control', [MissionControlController::class, 'index']);
});
