<?php

use App\Http\Controllers\User\Comparisons\ComparisonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Comparison ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/portfolio-compare/{portfolio_id}', [ComparisonController::class, 'showPortfolioComparison']);
    Route::get('/compare-symbols', [ComparisonController::class, 'compareSymbols']);
    Route::get('/metric-explorer', [ComparisonController::class, 'metricExplorer']);
    Route::get('/compare-etfs', [ComparisonController::class, 'compareEtfs']);
});
