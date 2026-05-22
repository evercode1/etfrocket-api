<?php

namespace App\Http\Controllers\User\Etfs;

use App\Http\Controllers\Controller;
use App\Queries\Etfs\CompareEtfsQuery;
use App\Services\EtfComparisons\EtfComparisonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Utilities\Auth;

class EtfComparisonController extends Controller
{
    public function compareEtfs(
        Request $request,
        EtfComparisonService $comparisonService
    ) {

        Log::info($request->all());
        try {

            $resolved = $comparisonService->resolve(
                $request->all()
            );

            $comparison = (new CompareEtfsQuery())->getData(
                $resolved
            );
        } catch (\Exception $e) {

            Log::error('Failed to compare ETFs', [

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
