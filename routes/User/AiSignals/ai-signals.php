<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AiSignals\AiSignalsController;


/*
|--------------------------------------------------------------------------
| User Ai Signals ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::get('/get-ai-signals', [AiSignalsController::class, 'index']);

Route::group(['middleware' => ['auth:sanctum']], function () {});
