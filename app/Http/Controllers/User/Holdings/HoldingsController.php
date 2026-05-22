<?php

namespace App\Http\Controllers\User\Holdings;

use App\Http\Controllers\Controller;
use App\Services\Holdings\PortfolioHoldingsAnalysisService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HoldingsController extends Controller
{
    public function portfolioHoldings(
        int $portfolio_id,
        PortfolioHoldingsAnalysisService $service
    ) {

        $user_id = Auth::id();
        try {
            $data = $service->getData($user_id, $portfolio_id);
        } catch (\Exception $e) {
            Log::error('Failed to load portfolio holdings', [
                'user_id' => $user_id,
                'portfolio_id' => $portfolio_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load holdings at this time.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
