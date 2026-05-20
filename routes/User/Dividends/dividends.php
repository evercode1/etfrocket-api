<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\Dividends\DividendIntelligenceController;


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
});
