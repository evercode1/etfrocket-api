<?php

use App\Http\Controllers\Admin\Monitoring\MonitoringController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Monitoring ROUTES
|--------------------------------------------------------------------------
|
| Routes for monitoring.
|
*/

Route::group(['middleware' => ['auth:sanctum', 'isAdmin']], function () {

    Route::get('/admin/pipeline-health', [MonitoringController::class, 'pipelineHealth']);
    Route::post('/admin/pipeline-health/runs', [MonitoringController::class, 'startManualRun']);
    Route::get('/admin/pipeline-health/runs/latest', [MonitoringController::class, 'latestManualRun']);
    Route::get('/admin/pipeline-health/runs/{id}', [MonitoringController::class, 'showManualRun'])->whereNumber('id');
    Route::get('/admin/cron-reports', [MonitoringController::class, 'cronReports']);
    Route::get('/import-logs', [MonitoringController::class, 'listImportLogs']);
    Route::get('/import-log/{id}', [MonitoringController::class, 'showImportLog']);
});
