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
    Route::get('/admin/admin-selects/{key}', [AdminSelectsController::class, 'show']);
    Route::post('/admin/admin-selects/{key}', [AdminSelectsController::class, 'store']);
    Route::put('/admin/admin-selects/{key}/{id}', [AdminSelectsController::class, 'update']);
    Route::delete('/admin/admin-selects/{key}/{id}', [AdminSelectsController::class, 'destroy']);

});
