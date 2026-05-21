<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\User\Portfolios\PortfolioSignalsController;


/*
|--------------------------------------------------------------------------
| User Portfolios ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum']], function () {


    Route::get('/portfolio-distribution-growth-signal/{portfolio_id}', [PortfolioSignalsController::class, 'showDistributionGrowthSignal']);
});
