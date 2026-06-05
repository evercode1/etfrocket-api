<?php

namespace App\Http\Controllers\Admin\EtfIssuers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEtfIssuerRequest;
use App\Http\Requests\UpdateEtfIssuerRequest;
use App\Models\Status;
use App\Queries\Admin\EtfIssuers\ListEtfIssuersQuery;
use App\Queries\Admin\EtfIssuers\ShowEtfIssuerQuery;
use App\Services\Admin\EtfIssuers\CreateEtfIssuerService;
use App\Services\Admin\EtfIssuers\RetireEtfIssuerService;
use App\Services\Admin\EtfIssuers\UpdateEtfIssuerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EtfIssuersController extends Controller
{
    public function index(Request $request)
    {
        try {

            $result =

                app(
                    ListEtfIssuersQuery::class
                )->getData(
                    $request
                );

            return response()->json([

                'success' => true,

                'data' => $result['data'],

                'meta' => $result['meta'],

            ], 200);

        } catch (\Exception $e) {

            Log::error(
                'Failed to list ETF issuers',
                [
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to load ETF issuers.',

            ], 500);
        }
    }

    public function issuerSelects()
    {
        try {

            return response()->json([

                'success' => true,

                'data' => [

                    'statuses' => Status::getSelects(),

                ],

            ], 200);

        } catch (\Exception $e) {

            Log::error(
                'Failed to load ETF issuer selects',
                [
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to load ETF issuer selects.',

            ], 500);
        }
    }

    public function show(int $id)
    {
        try {

            return response()->json([

                'success' => true,

                'data' => app(
                    ShowEtfIssuerQuery::class
                )->getData(
                    $id
                ),

            ], 200);

        } catch (\Exception $e) {

            Log::error(
                'Failed to load ETF issuer',
                [
                    'issuer_id' => $id,
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([

                'success' => false,

                'message' => 'ETF issuer not found.',

            ], 500);
        }
    }

    public function store(
        StoreEtfIssuerRequest $request
    ) {
        try {

            $issuer =

                app(
                    CreateEtfIssuerService::class
                )->store(
                    $request->validated()
                );

            return response()->json([

                'success' => true,

                'data' => $issuer,

            ], 200);

        } catch (\Exception $e) {

            Log::error(
                'Failed to create ETF issuer',
                [
                    'payload' => $request->all(),
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to create ETF issuer.',

            ], 500);
        }
    }

    public function update(
        UpdateEtfIssuerRequest $request,
        int $id
    ) {
        try {

            $issuer =

                app(
                    UpdateEtfIssuerService::class
                )->update(
                    $id,
                    $request->validated()
                );

            return response()->json([

                'success' => true,

                'data' => $issuer,

            ], 200);

        } catch (\Exception $e) {

            Log::error(
                'Failed to update ETF issuer',
                [
                    'issuer_id' => $id,
                    'payload' => $request->all(),
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to update ETF issuer.',

            ], 500);
        }
    }

    public function retire(int $id)
    {
        try {

            $issuer =

                app(
                    RetireEtfIssuerService::class
                )->retire(
                    $id
                );

            return response()->json([

                'success' => true,

                'data' => $issuer,

            ], 200);

        } catch (\Exception $e) {

            Log::error(
                'Failed to retire ETF issuer',
                [
                    'issuer_id' => $id,
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to retire ETF issuer.',

            ], 500);
        }
    }
}
