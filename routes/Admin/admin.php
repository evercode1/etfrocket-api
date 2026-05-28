<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be Admin and logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum', 'isAdmin']], function () {});
