<?php

namespace App\Http\Controllers\Admin\Securities;

use App\Http\Controllers\Controller;
use App\Queries\Admin\Securities\ListSecuritiesDataQuery;
use Illuminate\Support\Facades\Log;

class SecuritiesController extends Controller
{
    public function index()
    {
        try {

            return response()->json([

                'success' => true,

                'data' => app(

                    ListSecuritiesDataQuery::class

                )->getData(),

            ], 200);

        } catch (\Exception $e) {

            Log::error(

                'Failed to list securities data',

                [

                    'error' => $e->getMessage(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to retrieve securities.',

            ], 500);
        }
    }
}
