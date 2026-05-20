<?php

namespace App\Http\Controllers\User\Dividends;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Queries\Dividends\UpcomingWeeklyDividendsQuery;
use Illuminate\Support\Facades\Auth;

class DividendCalendarController extends Controller
{
    public function index(
        int $portfolio_id,
        UpcomingWeeklyDividendsQuery $query
    ) {
        try {
            Portfolio::where('id', $portfolio_id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $events = $query->getData($portfolio_id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'events' => $events,
            ],
        ], 200);
    }
}
