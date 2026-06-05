<?php

use App\Http\Controllers\Admin\Selects\AdminSelectsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Selects ROUTES
|--------------------------------------------------------------------------
|
| routes for admin select functionalities, requires authentication
|
|
|
*/

Route::group(['middleware' => ['auth:sanctum', 'isAdmin']], function () {

    Route::get('/admin/admin-selects', [AdminSelectsController::class, 'index']);

});
