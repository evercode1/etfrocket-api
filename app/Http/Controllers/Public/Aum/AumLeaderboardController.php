<?php

namespace App\Http\Controllers\Public\Aum;

use App\Http\Controllers\Controller;
use App\Queries\Aum\AumLeaderboardQuery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AumLeaderboardController extends Controller
{
    public const CACHE_KEY = 'public:aum-leaderboard';

    public const CACHE_SECONDS = 600;

    public function index(AumLeaderboardQuery $query)
    {
        try {

            $leaderboard = Cache::remember(
                self::CACHE_KEY,
                self::CACHE_SECONDS,
                fn () => $query->getData()
            );
        } catch (\Exception $e) {

            Log::error('Failed to load AUM leaderboard', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $leaderboard,
        ], 200);
    }
}
