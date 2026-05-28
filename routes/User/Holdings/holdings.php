<?php

use App\Http\Controllers\User\Holdings\HoldingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Holdings ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/portfolio-holdings/{portfolio_id}', [HoldingsController::class, 'portfolioHoldings']);
});
