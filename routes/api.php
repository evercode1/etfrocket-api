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
| BackTesting ROUTES
|--------------------------------------------------------------------------
|
| routes for user backtesting, requires authentication
|
*/

IncludeRoutes::file('routes/User/BackTesting/back-testing.php');

/*
|--------------------------------------------------------------------------
| Comparison ROUTES
|--------------------------------------------------------------------------
|
| routes for user comparisons, requires authentication
|
*/

IncludeRoutes::file('routes/User/Comparisons/comparisons.php');

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
| Holdings ROUTES
|--------------------------------------------------------------------------
|
| routes for user holdings, requires authentication
|
*/

IncludeRoutes::file('routes/User/Holdings/holdings.php');

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
| Portfolio stats ROUTES
|--------------------------------------------------------------------------
|
| routes for user portfolio stats, requires authentication
|
*/

IncludeRoutes::file('routes/User/Portfolios/portfolio-stats.php');

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
