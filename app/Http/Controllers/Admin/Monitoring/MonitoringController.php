<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Queries\Admin\Monitoring\CronReportQuery;
use App\Queries\Admin\Monitoring\PipelineHealthQuery;
use App\Services\Admin\Monitoring\ManualFundDataRunService;
use Illuminate\Http\Request;
use App\Queries\ImportLogs\ListImportLogsQuery;
use App\Queries\ImportLogs\ShowImportLogQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MonitoringController extends Controller
{
    public function pipelineHealth(
        PipelineHealthQuery $query
    ): JsonResponse {

        try {

            $data = $query->getData();
        } catch (\Exception $e) {

            Log::error('Failed to fetch pipeline health', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pipeline health.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

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

    /*
    |--------------------------------------------------------------------------
    | Manual fund data (AUM) runs
    |--------------------------------------------------------------------------
    |
    | POST body: scope = problems | issuer | all; issuer_id when scope=issuer.
    | 409 while another manual run is in progress; 422 when nothing matches.
    |
    */

    public function startManualRun(
        Request $request,
        ManualFundDataRunService $service
    ): JsonResponse {

        $validated = $request->validate([
            'scope' => 'required|in:'.implode(',', ManualFundDataRunService::SCOPES),
            'issuer_id' => 'required_if:scope,issuer|nullable|integer|exists:etf_issuers,id',
        ]);

        try {

            $run = $service->start(
                $validated['scope'],
                isset($validated['issuer_id']) ? (int) $validated['issuer_id'] : null
            );
        } catch (\DomainException $e) {

            $inProgress = str_contains($e->getMessage(), 'already in progress');

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $inProgress ? 409 : 422);
        } catch (\Exception $e) {

            Log::error('Failed to start manual fund data run', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start the AUM update.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $run,
        ], 201);
    }

    public function latestManualRun(
        ManualFundDataRunService $service
    ): JsonResponse {

        return response()->json([
            'success' => true,
            'data' => $service->latest(),
        ], 200);
    }

    public function showManualRun(
        int $id,
        ManualFundDataRunService $service
    ): JsonResponse {

        $run = $service->find($id);

        if (! $run) {

            return response()->json([
                'success' => false,
                'message' => 'Run not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $run,
        ], 200);
    }
}
