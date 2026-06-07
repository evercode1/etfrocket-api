<?php

use App\Http\Controllers\Public\HealthCheck\HealthCheckController;
use App\Utilities\IncludeRoutes;
use Illuminate\Support\Facades\Route;

Route::get('/health/check', [HealthCheckController::class, 'check']);

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
| Admin Etf Issuer ROUTES
|--------------------------------------------------------------------------
|
| routes for etf issuer functionalities, requires authentication
|
*/

IncludeRoutes::file('routes/Admin/EtfIssuers/etf-issuers.php');

/*
|--------------------------------------------------------------------------
| Monitoring ROUTES
|--------------------------------------------------------------------------
|
| routes for monitoring functionalities, requires authentication
|
*/

IncludeRoutes::file('routes/Admin/Monitoring/monitoring.php');

/*
|--------------------------------------------------------------------------
| Admin Selects ROUTES
|--------------------------------------------------------------------------
|
| routes for admin select functionalities, requires authentication
|
*/

IncludeRoutes::file('routes/Admin/Selects/admin-selects.php');

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
| Admin Security Data ROUTES
|--------------------------------------------------------------------------
|
| routes for security data, requires authentication
|
*/

IncludeRoutes::file('routes/Admin/SecurityData/security-data.php');

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
| Ai Signals ROUTES
|--------------------------------------------------------------------------
|
| routes for user AI signals, requires authentication
|
*/

IncludeRoutes::file('routes/User/AiSignals/ai-signals.php');

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
| Securities ROUTES
|--------------------------------------------------------------------------
|
| routes for user securities, requires authentication
|
*/

IncludeRoutes::file('routes/User/Securities/securities.php');

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
