<?php

namespace App\Http\Controllers\Public\Aum;

use App\Http\Controllers\Controller;
use App\Queries\Aum\AumHistoryQuery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AumHistoryController extends Controller
{
    public const CACHE_SECONDS = 600;

    public static function cacheKey(string $symbol): string
    {
        return 'public:aum-history:'.strtoupper($symbol);
    }

    public function show(string $symbol, AumHistoryQuery $query)
    {
        try {

            $history = Cache::remember(
                self::cacheKey($symbol),
                self::CACHE_SECONDS,
                fn () => $query->getData($symbol)
            );
        } catch (\Exception $e) {

            Log::error('Failed to load AUM history', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        if (! $history) {

            return response()->json([
                'success' => false,
                'message' => 'ETF not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $history,
        ], 200);
    }
}
