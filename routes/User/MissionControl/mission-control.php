<?php

use App\Http\Controllers\User\MissionControl\MissionControlController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/portfolio-income-projection/{portfolio_id}', [
        MissionControlController::class,
        'portfolioIncomeProjection',
    ]);
});
