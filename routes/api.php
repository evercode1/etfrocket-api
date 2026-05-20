<?php

use Illuminate\Support\Facades\Route;
use App\Utilities\IncludeRoutes;

Route::get('/health/check', function () {

    return response()->json(['status' => 'OK', 'code' => 200, 'message' => 'healthy']);
});

/*
|--------------------------------------------------------------------------
| Admin ROUTES
|--------------------------------------------------------------------------
|
| routes for admin functionalities, requires authentication
|
*/

IncludeRoutes::file('routes/Admin/admin.php');

/*
|--------------------------------------------------------------------------
| Admin Support ROUTES
|--------------------------------------------------------------------------
|
| routes for admin functionalities, requires authentication
|s
*/

IncludeRoutes::file('routes/Admin/Support/admin-support.php');

/*
|--------------------------------------------------------------------------
| Admin Etf Data ROUTES
|--------------------------------------------------------------------------
|
| routes for admin functionalities, requires authentication
|
*/

IncludeRoutes::file('routes/Admin/EtfData/etf-data.php');


/*
|--------------------------------------------------------------------------
| Seed ROUTES
|--------------------------------------------------------------------------
|
| routes for seeds
|
*/

IncludeRoutes::file('routes/Admin/Seeds/seeds.php');


/*
|--------------------------------------------------------------------------
| Auth ROUTES
|--------------------------------------------------------------------------
|
| routes for authentication
|
*/

IncludeRoutes::file('routes/Auth/auth.php');


/*
|--------------------------------------------------------------------------
| User ROUTES
|--------------------------------------------------------------------------
|
| routes for user functionalities
|
*/

/*
|--------------------------------------------------------------------------
| Dividends ROUTES
|--------------------------------------------------------------------------
|
| routes for user dividends, requires authentication
|
*/

IncludeRoutes::file('routes/User/Dividends/dividends.php');

/*
|--------------------------------------------------------------------------
| Etfs ROUTES
|--------------------------------------------------------------------------
|
| routes for user etfs, requires authentication
|
*/

IncludeRoutes::file('routes/User/Etfs/etfs.php');

/*
|--------------------------------------------------------------------------
| Mission Control ROUTES
|--------------------------------------------------------------------------
|
| routes for user mission control, requires authentication
|
*/

IncludeRoutes::file('routes/User/MissionControl/mission-control.php');


/*
|--------------------------------------------------------------------------
| Portfolio ROUTES
|--------------------------------------------------------------------------
|
| routes for user portfolio, requires authentication
|
*/

IncludeRoutes::file('routes/User/Portfolios/portfolios.php');

/*
|--------------------------------------------------------------------------
| Settings ROUTES
|--------------------------------------------------------------------------
|
| routes for user settings, requires authentication
|
*/

IncludeRoutes::file('routes/User/Settings/settings.php');


/*
|--------------------------------------------------------------------------
| Support ROUTES
|--------------------------------------------------------------------------
|
| routes for user support, requires authentication
|
*/

IncludeRoutes::file('routes/User/Support/support.php');
