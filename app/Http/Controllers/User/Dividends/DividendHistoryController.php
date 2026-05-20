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
        $portfolio_id,
        DividendHistoryQuery $query
    ) {
        try {
            $data = $query->getData(
                $request,
                Auth::id(),
                (int) $portfolio_id
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
}
