<?php

namespace App\Http\Controllers\User\Portfolios;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Services\PortfolioStats\Signals\PortfolioDistributionGrowthSignalService;
use Illuminate\Support\Facades\Auth;

class PortfolioSignalsController extends Controller
{
    public function showDistributionGrowthSignal(

        int $portfolio_id,

        PortfolioDistributionGrowthSignalService $service

    ) {

        try {

            Portfolio::where('id', $portfolio_id)

                ->where('user_id', Auth::id())

                ->firstOrFail();

            $data = $service->getSignalData($portfolio_id);
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
}
