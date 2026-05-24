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
});
