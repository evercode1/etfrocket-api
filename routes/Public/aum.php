<?php

use App\Http\Controllers\Public\Aum\AumHistoryController;
use App\Http\Controllers\Public\Aum\AumLeaderboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public AUM ROUTES
|--------------------------------------------------------------------------
|
| No authentication. Responses are cached and requests are rate limited.
|
*/

Route::group(['middleware' => ['throttle:60,1']], function () {

    Route::get('/aum-leaderboard', [AumLeaderboardController::class, 'index']);

    Route::get('/aum-history/{symbol}', [AumHistoryController::class, 'show'])
        ->where('symbol', '[A-Za-z0-9.\-]{1,12}');

});
