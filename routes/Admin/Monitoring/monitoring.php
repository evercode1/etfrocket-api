<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Monitoring\MonitoringController;

/*
|--------------------------------------------------------------------------
| Monitoring ROUTES
|--------------------------------------------------------------------------
|
| Routes for monitoring.
|
*/

Route::group(['middleware' => ['auth:sanctum', 'isAdmin']], function () {


    Route::get('/admin/cron-reports', [MonitoringController::class, 'cronReports']);
    Route::get('/import-logs', [MonitoringController::class, 'listImportLogs']);
    Route::get('/import-log/{id}', [MonitoringController::class, 'showImportLog']);
});
