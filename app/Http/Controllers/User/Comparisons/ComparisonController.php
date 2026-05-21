<?php

namespace App\Http\Controllers\User\Comparisons;

use App\Http\Controllers\Controller;
use App\Services\Comparisons\PortfolioCompareService;
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
}
