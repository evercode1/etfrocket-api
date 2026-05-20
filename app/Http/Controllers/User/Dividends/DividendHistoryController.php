<?php

namespace App\Http\Controllers\User\Dividends;

use App\Http\Controllers\Controller;
use App\Queries\Dividends\DividendHistoryQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DividendHistoryController extends Controller
{
    public function index(
        Request $request,
        int $portfolio_id,
        DividendHistoryQuery $query
    ) {
        try {
            $dividends = $query->getData(
                $request,
                Auth::id(),
                $portfolio_id
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $dividends,
        ], 200);
    }
}
