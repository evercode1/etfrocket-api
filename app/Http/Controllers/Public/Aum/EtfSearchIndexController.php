<?php

namespace App\Http\Controllers\Public\Aum;

use App\Http\Controllers\Controller;
use App\Queries\Aum\EtfSearchIndexQuery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EtfSearchIndexController extends Controller
{
    public const CACHE_KEY = 'public:etf-search-index';

    public const CACHE_SECONDS = 600;

    public function index(EtfSearchIndexQuery $query)
    {
        try {

            $etfs = Cache::remember(
                self::CACHE_KEY,
                self::CACHE_SECONDS,
                fn () => $query->getData()
            );
        } catch (\Exception $e) {

            Log::error('Failed to load ETF search index', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $etfs,
        ], 200);
    }
}
