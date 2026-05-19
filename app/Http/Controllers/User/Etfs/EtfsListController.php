<?php

namespace App\Http\Controllers\User\Etfs;

use App\Http\Controllers\Controller;
use App\Queries\Etfs\FilteredEtfsQuery;
use App\Services\EtfFilters\EtfFilterService;
use App\Models\PortfolioTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Utilities\Auth;

class EtfsListController extends Controller
{
    public function listEtfs(Request $request, EtfFilterService $filterService)
    {
        try {

            $filters = $filterService->resolve($request->all());

            $etfs = (new FilteredEtfsQuery())->getData(
                $filters,
                Auth::id()
            );
        } catch (\Exception $e) {

            Log::error('Failed to fetch filtered ETFs', [
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
            'data' => $etfs,
        ], 200);
    }

    public function listEtfsOwnedByUser($portfolioId)
    {
        try {

            $etfs = PortfolioTransaction::where('portfolio_transactions.portfolio_id', $portfolioId)

                ->leftJoin('etfs', 'portfolio_transactions.etf_id', '=', 'etfs.id')

                ->orderBy('etfs.symbol', 'asc')

                ->distinct()

                ->pluck('etfs.symbol', 'etfs.id');
        } catch (\Exception $e) {

            Log::error('Failed to fetch user-owned ETFs', [
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
            'data' => $etfs,
        ], 200);
    }
}
