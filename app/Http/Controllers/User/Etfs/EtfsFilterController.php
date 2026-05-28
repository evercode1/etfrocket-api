<?php

namespace App\Http\Controllers\User\Etfs;

use App\Http\Controllers\Controller;
use App\Models\Etf;
use App\Services\EtfFilters\EtfFilterService;
use Illuminate\Http\Request;

class EtfsFilterController extends Controller
{
    public function getFilters(Request $request, EtfFilterService $filterService)
    {

        $filters = $filterService->getOptions();

        return response()->json([

            'success' => true,
            'data' => $filters,

        ], 200);
    }

    public function getSelects()
    {

        $selects = Etf::getSelects();

        return response()->json([

            'success' => true,
            'data' => $selects,

        ], 200);
    }
}
