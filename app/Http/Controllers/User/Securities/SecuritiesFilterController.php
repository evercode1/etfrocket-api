<?php

namespace App\Http\Controllers\User\Securities;

use App\Http\Controllers\Controller;
use App\Models\Security;
use App\Services\SecurityFilters\SecurityFilterService;

class SecuritiesFilterController extends Controller
{
    public function getFilters(SecurityFilterService $filterService)
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
