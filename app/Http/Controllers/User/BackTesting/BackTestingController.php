<?php

namespace App\Http\Controllers\User\BackTesting;

use App\Http\Controllers\Controller;
use App\Services\BackTesting\BackTestingService;
use Illuminate\Http\Request;

class BackTestingController extends Controller
{
    public function index(
        Request $request,
        BackTestingService $backTestingService
    ) {

        $request->validate([

            'etf_id' => [

                'required',

                'integer',

                'min:1',

            ],

            'start_date' => [

                'required',

                'date',

            ],

            'end_date' => [

                'required',

                'date',

                'after:start_date',

            ],

            'initial_investment' => [

                'required',

                'numeric',

                'min:1',

            ],

            'monthly_contribution' => [

                'nullable',

                'numeric',

                'min:0',

            ],

            'drip_percentage' => [

                'nullable',

                'numeric',

                'min:0',

                'max:100',

            ],

        ]);

        try {

            $data = $backTestingService
                ->getData(

                    etfId: (int) $request->etf_id,

                    startDate: $request->start_date,

                    endDate: $request->end_date,

                    initialInvestment: (float)
                    $request->initial_investment,

                    monthlyContribution: (float)
                    $request->get(
                        'monthly_contribution',
                        0
                    ),

                    dripPercentage: (float)
                    $request->get(
                        'drip_percentage',
                        100
                    ),

                );
        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' =>
                'Oops, something went wrong. Please try again later.',

            ], 500);
        }

        return response()->json([

            'success' => true,

            'data' => $data,

        ], 200);
    }
}
