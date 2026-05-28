<?php

namespace App\Http\Controllers\User\AiSignals;

use App\Http\Controllers\Controller;
use App\Queries\AiSignals\GetLatestAiSignalsQuery;
use App\Services\AI\AiSignals\IsMarketOpenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AiSignalsController extends Controller
{
    public function index(
        GetLatestAiSignalsQuery $query,
        IsMarketOpenService $isMarketOpenService
    ): JsonResponse {

        try {

            $signals =
                $query->getData();

            $isOpen =
                $isMarketOpenService
                    ->isOpen();

            return response()->json([

                'success' => true,

                'market' => [

                    'is_open' => $isOpen,

                    'status' => $isOpen
                        ? 'OPEN'
                        : 'CLOSED',

                ],

                'data' => $signals,

            ], 200);
        } catch (\Exception $e) {

            Log::error(
                'Failed to fetch AI signals',
                [

                    'error' => $e->getMessage(),

                ]
            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to fetch AI signals.',

            ], 500);
        }
    }
}
