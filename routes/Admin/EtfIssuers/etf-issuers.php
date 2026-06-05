<?php

use App\Http\Controllers\Admin\EtfIssuers\EtfIssuersController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum', 'isAdmin']], function () {

    Route::get(
        '/admin/list-etf-issuers',
        [EtfIssuersController::class, 'index']
    );

    Route::get(
        '/admin/etf-issuer-selects',
        [EtfIssuersController::class, 'issuerSelects']
    );

    Route::get(
        '/admin/etf-issuer-show/{id}',
        [EtfIssuersController::class, 'show']
    );

    Route::post(
        '/admin/etf-issuer-store',
        [EtfIssuersController::class, 'store']
    );

    Route::put(
        '/admin/etf-issuer-update/{id}',
        [EtfIssuersController::class, 'update']
    );

    Route::put(
        '/admin/etf-issuer-retire/{id}',
        [EtfIssuersController::class, 'retire']
    );

});
