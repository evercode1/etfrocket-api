<?php

namespace App\Http\Controllers\User\Dividends;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Dividends\DividendIntelligenceService;

class DividendIntelligenceController extends Controller
{
    public function show(
        int $portfolio_id,
        DividendIntelligenceService $service
    ) {
        try {
            $data = $service->getData(Auth::id(), $portfolio_id);
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
