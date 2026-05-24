<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Queries\Admin\Monitoring\CronReportQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MonitoringController extends Controller
{
    public function cronReports(
        CronReportQuery $query
    ): JsonResponse {

        try {

            $data =
                $query->getData();

            return response()->json([

                'success' => true,

                'data' => $data,

            ], 200);
        } catch (\Exception $e) {

            Log::error(

                'Failed to fetch cron reports',

                [

                    'error' =>
                    $e->getMessage(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' =>
                'Failed to fetch cron reports.',

            ], 500);
        }
    }
}
