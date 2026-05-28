<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Queries\Admin\Monitoring\CronReportQuery;
use App\Queries\ImportLogs\ListImportLogsQuery;
use App\Queries\ImportLogs\ShowImportLogQuery;
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

                    'error' => $e->getMessage(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to fetch cron reports.',

            ], 500);
        }
    }

    public function listImportLogs(
        ListImportLogsQuery $query
    ) {

        try {

            $logs =
                $query->getData();

            return response()->json([

                'success' => true,

                'logs' => $logs,

            ]);
        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' => 'Failed to load import logs.',

            ], 500);
        }
    }

    public function showImportLog(
        int $id,
        ShowImportLogQuery $query
    ) {

        try {

            $log =
                $query->getData($id);

            return response()->json([

                'success' => true,

                'log' => $log,

            ]);
        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' => 'Import log not found.',

            ], 404);
        }
    }
}
