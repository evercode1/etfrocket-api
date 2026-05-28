<?php

use App\Http\Controllers\Admin\ExternalData\ExternalDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
|
|
| Admin Etf Data ROUTES
|--------------------------------------------------------------------------
|
|
|
*/

Route::group(['middleware' => ['allowExternalData']], function () {

    Route::post('/etf-data', [ExternalDataController::class, 'updateEtfData']);

});
