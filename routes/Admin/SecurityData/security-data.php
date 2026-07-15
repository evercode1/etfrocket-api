<?php

use App\Http\Controllers\Admin\Securities\DividendsHistoryController;
use App\Http\Controllers\Admin\Securities\PriceHistoryController;
use App\Http\Controllers\Admin\Securities\SecuritiesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
|
| Admin Security Data ROUTES
|--------------------------------------------------------------------------
|
|
|
*/

Route::group(['middleware' => ['auth:sanctum', 'isAdmin']], function () {

    Route::get('/admin/list-securities-data', [SecuritiesController::class, 'index']);

    Route::get('/admin/security-data-selects', [SecuritiesController::class, 'securitySelects']);

    Route::get('/admin/security-data-show/{id}', [SecuritiesController::class, 'show']);

    Route::post('/admin/security-data-store', [SecuritiesController::class, 'store']);

    Route::put('/admin/security-data-update/{id}', [SecuritiesController::class, 'update']);

    Route::put('/admin/security-data-retire/{id}', [SecuritiesController::class, 'retire']);

    // security history routes

    Route::put('/admin/data/price-history', [PriceHistoryController::class, 'update']);
    Route::put('/admin/data/dividend-history', [DividendsHistoryController::class, 'update']);

});
