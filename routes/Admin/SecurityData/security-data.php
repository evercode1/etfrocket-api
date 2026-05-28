<?php

use App\Http\Controllers\Admin\ExternalData\ExternalDataController;
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

Route::group(['middleware' => ['allowExternalData']], function () {

    Route::post('/security-data', [ExternalDataController::class, 'updateSecurityData']);

});
