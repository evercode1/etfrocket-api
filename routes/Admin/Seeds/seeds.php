<?php

use App\Http\Controllers\Admin\DataSeeds\SecurityDataSeedController;
use App\Http\Controllers\Dev\ExternalSeeders\MakeAdminUsersSeederController;
use App\Http\Controllers\Dev\ExternalSeeders\MakeSeedsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Seeder ROUTES
|--------------------------------------------------------------------------
|
| Routes for seeders.
|
*/

Route::group(['middleware' => ['allowSeeds']], function () {

    Route::get('/make-seeds', [MakeSeedsController::class, 'index']);
    Route::get('/make-admin-users', [MakeAdminUsersSeederController::class, 'run']);
    Route::get('/make-seed', [MakeSeedsController::class, 'makeSeed']);
    Route::get('/drop-seed', [MakeSeedsController::class, 'dropSeed']);

    Route::post('/data/backfill-price-history', [SecurityDataSeedController::class, 'backfillPriceHistory']);

    Route::post('/data/calculate-security-metrics', [SecurityDataSeedController::class, 'calculateSecurityMetrics']);

    Route::post('/data/run-ai-data-extractions', [SecurityDataSeedController::class, 'runAiDataExtractions']);

    Route::post('/data/truncate-tables', [SecurityDataSeedController::class, 'truncateTables']);
});
