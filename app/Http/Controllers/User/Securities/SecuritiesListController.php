<?php

namespace App\Http\Controllers\User\Securities;

use App\Http\Controllers\Controller;
use App\Models\PortfolioTransaction;
use App\Queries\Securities\FilteredSecuritiesQuery;
use App\Services\SecurityFilters\SecurityFilterService;
use App\Utilities\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecuritiesListController extends Controller
{
    public function listSecurities(Request $request, SecurityFilterService $filterService)
    {
        try {

            $filters = $filterService->resolve($request->all());

            $securities = (new FilteredSecuritiesQuery)->getData(
                $filters,
                Auth::id()
            );
        } catch (\Exception $e) {

            Log::error('Failed to fetch filtered securities', [
                'user_id' => Auth::id(),
                'filters' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $securities,
        ], 200);
    }

    public function listSecuritiesOwnedByUser($portfolioId)
    {
        try {

            $securities = PortfolioTransaction::where('portfolio_transactions.portfolio_id', $portfolioId)

                ->leftJoin('securities', 'portfolio_transactions.security_id', '=', 'securities.id')

                ->select([

                    'securities.id',

                    'securities.symbol',

                ])

                ->distinct()

                ->orderBy('securities.symbol', 'asc')

                ->get();
        } catch (\Exception $e) {

            Log::error('Failed to fetch user-owned securities', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $securities,
        ], 200);
    }
}
