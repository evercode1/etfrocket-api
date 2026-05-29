<?php

namespace App\Http\Controllers\Admin\DataSeeds;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SecurityDataSeedController extends Controller
{
    public function backfillPriceHistory(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string|max:20',
        ]);

        $exitCode = Artisan::call('etfs:backfill-price-history', [
            'symbol' => strtoupper(trim($request->input('symbol'))),
        ]);

        return response()->json([
            'status' => $exitCode === 0 ? 'success' : 'error',
            'message' => Artisan::output(),
        ], $exitCode === 0 ? 200 : 500);
    }

    public function calculateSecurityMetrics(Request $request)
    {
        $request->validate([
            'symbol' => 'nullable|string|max:20',
        ]);

        $params = [];

        if ($request->filled('symbol')) {

            $params['--symbol'] = strtoupper(
                trim($request->input('symbol'))
            );
        }

        $exitCode = Artisan::call(
            'securities:calculate-metrics',
            $params
        );

        return response()->json([
            'status' => $exitCode === 0 ? 'success' : 'error',
            'message' => Artisan::output(),
        ], $exitCode === 0 ? 200 : 500);
    }

    public function runAiDataExtractions(Request $request)
    {
        $request->validate([
            'symbol' => 'nullable|string|max:20',
            'limit' => 'nullable|integer|min:1',
        ]);

        $params = [];

        if ($request->filled('symbol')) {

            $params['--symbol'] = strtoupper(
                trim($request->input('symbol'))
            );
        }

        if ($request->filled('limit')) {

            $params['--limit'] = (int) $request->input('limit');
        }

        $exitCode = Artisan::call(
            'securities:run-ai-extraction',
            $params
        );

        return response()->json([
            'status' => $exitCode === 0 ? 'success' : 'error',
            'message' => Artisan::output(),
        ], $exitCode === 0 ? 200 : 500);
    }

    public function truncateTables(Request $request)
    {
        $request->validate([
            'tables' => 'required|string',
        ]);

        $exitCode = Artisan::call('db:truncate-table', [
            'tables' => $request->input('tables'),
        ]);

        return response()->json([
            'status' => $exitCode === 0 ? 'success' : 'error',
            'message' => Artisan::output(),
        ], $exitCode === 0 ? 200 : 500);
    }
}
