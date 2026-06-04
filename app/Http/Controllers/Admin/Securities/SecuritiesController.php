<?php

namespace App\Http\Controllers\Admin\Securities;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSecurityRequest;
use App\Http\Requests\UpdateSecurityRequest;
use App\Models\DistributionFrequency;
use App\Models\EtfIssuer;
use App\Models\EtfStrategyType;
use App\Models\SecurityType;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Queries\Admin\Securities\ListSecuritiesDataQuery;
use App\Queries\Admin\Securities\ShowSecurityDataQuery;
use App\Services\Admin\Securities\CreateSecurityService;
use App\Services\Admin\Securities\RetireSecurityService;
use App\Services\Admin\Securities\UpdateSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecuritiesController extends Controller
{
    public function index(Request $request)
    {
        try {

            return response()->json([

                'success' => true,

                'data' => app(

                    ListSecuritiesDataQuery::class

                )->getData($request),

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

    public function securitySelects()
    {
        try {

            return response()->json([

                'success' => true,

                'data' => [

                    'security_types' => SecurityType::getSelects(),

                    'statuses' => Status::getSelects(),

                    'etf_issuers' => EtfIssuer::getSelects(),

                    'etf_strategy_types' => EtfStrategyType::getSelects(),

                    'distribution_frequencies' => DistributionFrequency::getSelects(),

                    'security_update_types' => SecurityUpdateType::getSelects(),

                ],

            ]);

        } catch (\Exception $e) {

            Log::error(

                'Failed to retrieve security selects',

                [

                    'error' => $e->getMessage(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to retrieve security selects.',

            ], 500);
        }
    }

    public function show(int $id)
    {
        try {

            return response()->json([

                'success' => true,

                'data' => app(

                    ShowSecurityDataQuery::class

                )->getData($id),

            ]);

        } catch (\Exception $e) {

            Log::error(

                'Failed to retrieve security',

                [

                    'security_id' => $id,

                    'error' => $e->getMessage(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to retrieve security.',

            ], 500);
        }
    }

    public function store(
        StoreSecurityRequest $request
    ) {
        try {

            $security =

                app(
                    CreateSecurityService::class
                )->store(

                    $request->validated()

                );

            return response()->json([

                'success' => true,

                'data' => $security,

            ]);

        } catch (\Exception $e) {

            Log::error(

                'Failed to create security',

                [

                    'error' => $e->getMessage(),

                    'payload' => $request->all(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to create security.',

            ], 500);
        }
    }

    public function update(
        UpdateSecurityRequest $request,
        int $id
    ) {
        try {

            $security =

                app(
                    UpdateSecurityService::class
                )->update(

                    $id,

                    $request->validated()

                );

            return response()->json([

                'success' => true,

                'data' => $security,

            ]);

        } catch (\Exception $e) {

            Log::error(

                'Failed to update security',

                [

                    'security_id' => $id,

                    'error' => $e->getMessage(),

                    'payload' => $request->all(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to update security.',

            ], 500);
        }
    }

    public function retire(
        int $id
    ) {
        try {

            $security =

                app(
                    RetireSecurityService::class
                )->retire(
                    $id
                );

            return response()->json([

                'success' => true,

                'data' => $security,

            ]);

        } catch (\Exception $e) {

            Log::error(

                'Failed to retire security',

                [

                    'security_id' => $id,

                    'error' => $e->getMessage(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to retire security.',

            ], 500);
        }
    }
}
