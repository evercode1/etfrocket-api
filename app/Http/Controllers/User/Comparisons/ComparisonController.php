<?php

namespace App\Http\Controllers\User\Comparisons;

use App\Http\Controllers\Controller;
use App\Queries\Securities\CompareSecuritiesQuery;
use App\Services\Comparisons\CompareSymbolsService;
use App\Services\Comparisons\MetricExplorerService;
use App\Services\Comparisons\PortfolioCompareService;
use App\Services\Comparisons\SecurityComparisonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ComparisonController extends Controller
{
    public function showPortfolioComparison(

        Request $request,

        int $portfolio_id,

        PortfolioCompareService $service

    ) {

        $request->validate([

            'metric' => [

                'nullable',

                'string',

            ],

            'range' => [

                'nullable',

                'string',

            ],

        ]);

        try {

            $data = $service->getData(

                Auth::id(),

                $portfolio_id,

                $request->only(['metric', 'range'])

            );
        } catch (\Exception $e) {

            Log::error('Failed to load portfolio comparison data', [

                'user_id' => Auth::id(),

                'portfolio_id' => $portfolio_id,

                'error' => $e->getMessage(),

            ]);

            return response()->json([

                'success' => false,

                'message' => 'Oops, something went wrong. Please try again later.',

            ], 500);
        }

        return response()->json([

            'success' => true,

            'data' => $data,

        ], 200);
    }

    public function compareSymbols(

        Request $request,

        CompareSymbolsService $service

    ) {

        try {

            $data = $service->getData(

                symbols: $request->input('symbols', []),

                metric: $request->input('metric', 'price'),

                range: $request->input('range', '90d')

            );
        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' => 'Unable to compare symbols at this time.',

            ], 500);
        }

        return response()->json([

            'success' => true,

            'data' => $data,

        ]);
    }

    public function metricExplorer(
        Request $request,
        MetricExplorerService $metricExplorerService
    ) {
        $request->validate([

            'metric' => [

                'nullable',

                'string',

            ],

            'range' => [

                'nullable',

                'string',

            ],

            'sort_direction' => [

                'nullable',

                'in:asc,desc',

            ],

            'limit' => [

                'nullable',

                'integer',

                'min:1',

                'max:100',

            ],

        ]);

        try {

            $data = $metricExplorerService->getData(

                metric: $request->input('metric'),

                range: $request->input('range'),

                sortDirection: $request->input(
                    'sort_direction'
                ),

                limit: $request->input('limit'),

            );
        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' => 'Oops, something went wrong. Please try again later.',

            ], 500);
        }

        return response()->json([

            'success' => true,

            'data' => $data,

        ], 200);
    }

    public function compareSecurities(
        Request $request,
        SecurityComparisonService $comparisonService
    ) {

        Log::info($request->all());
        try {

            $resolved = $comparisonService->resolve(
                $request->all()
            );

            $comparison = (new CompareSecuritiesQuery)->getData(
                $resolved
            );
        } catch (\Exception $e) {

            Log::error('Failed to compare securities', [

                'user_id' => Auth::id(),

                'request' => $request->all(),

                'error' => $e->getMessage(),

            ]);

            return response()->json([

                'success' => false,

                'message' => 'Oops, something went wrong. Please try again later.',

            ], 500);
        }

        return response()->json([

            'success' => true,

            'data' => $comparison,

        ], 200);
    }
}
