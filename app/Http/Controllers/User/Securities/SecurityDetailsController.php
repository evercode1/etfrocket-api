<?php

namespace App\Http\Controllers\User\Securities;

use App\Http\Controllers\Controller;
use App\Queries\Securities\SecurityDetailsQuery;
use Illuminate\Http\Request;

class SecurityDetailsController extends Controller
{
    public function show(Request $request, string $symbol)
    {
        $performanceRangeTypeId = $request->input('performance_range_type_id');
        $startDate = $request->input('start_date');

        $query = app(
            SecurityDetailsQuery::class
        );

        return response()->json([

            'success' => true,

            'data' => $query->getData($symbol, $performanceRangeTypeId, $startDate),

        ]);
    }
}
