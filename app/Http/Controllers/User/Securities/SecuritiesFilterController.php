<?php

namespace App\Http\Controllers\User\Securities;

use App\Http\Controllers\Controller;
use App\Models\Security;
use App\Services\SecurityFilters\SecurityFilterService;
use Illuminate\Http\Request;

class SecuritiesFilterController extends Controller
{
    public function getFilters(Request $request, SecurityFilterService $filterService)
    {

        $filters = $filterService->getOptions();

        return response()->json([

            'success' => true,
            'data' => $filters,

        ], 200);
    }

    public function getSelects()
    {

        $selects = Security::getSelects();

        return response()->json([

            'success' => true,
            'data' => $selects,

        ], 200);
    }
}
