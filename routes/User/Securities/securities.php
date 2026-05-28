<?php

use App\Http\Controllers\User\Securities\SecuritiesFilterController;
use App\Http\Controllers\User\Securities\SecuritiesListController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Securities ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/get-security-filters', [SecuritiesFilterController::class, 'getFilters']);

    Route::get('/get-security-selects', [SecuritiesFilterController::class, 'getSelects']);

    Route::get('/list-securities', [SecuritiesListController::class, 'listSecurities']);

    Route::get('/list-securities-owned-by-user/{portfolioId}', [SecuritiesListController::class, 'listSecuritiesOwnedByUser']);
});
