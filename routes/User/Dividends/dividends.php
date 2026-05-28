<?php

use App\Http\Controllers\User\Dividends\DividendCalendarController;
use App\Http\Controllers\User\Dividends\DividendHistoryController;
use App\Http\Controllers\User\Dividends\DividendIntelligenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Dividends ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/dividend-intelligence/{portfolio_id}', [DividendIntelligenceController::class, 'show']);
    Route::get('/dividend-history/{portfolio_id}', [DividendHistoryController::class, 'index']);
    Route::get('/dividend-calendar/{portfolio_id}', [DividendCalendarController::class, 'index']);
});
