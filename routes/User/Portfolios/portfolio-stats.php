<?php

use App\Http\Controllers\User\Portfolios\PortfolioSignalsController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/portfolio-aum-growth-signal/{portfolio_id}', [PortfolioSignalsController::class, 'showAumGrowthSignal']);
    Route::get('/portfolio-nav-stability-signal/{portfolio_id}', [PortfolioSignalsController::class, 'showNavStabilitySignal']);
});
