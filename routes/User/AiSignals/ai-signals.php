<?php

use App\Http\Controllers\User\AiSignals\AiSignalsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Ai Signals ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/get-ai-signals', [AiSignalsController::class, 'index']);
});
