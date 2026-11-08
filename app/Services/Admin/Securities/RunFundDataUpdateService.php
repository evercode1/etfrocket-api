<?php

namespace App\Services\Admin\Securities;

use App\Http\Controllers\Public\Aum\AumHistoryController;
use App\Http\Controllers\Public\Aum\AumLeaderboardController;
use App\Http\Controllers\Public\Aum\EtfSearchIndexController;
use App\Models\ImportType;
use App\Models\Security;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Services\AI\Extractions\AiSecurityFundDataExtractionService;
use App\Services\AI\Extractions\ProcessAiSecurityFundDataExtractionService;
use App\Services\Aum\FundDataCoverage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/*
|--------------------------------------------------------------------------
| Run a fund data update now (admin "Update now")
|--------------------------------------------------------------------------
|
| Scrapes one security's AUM / NAV immediately, using the same extract +
| process steps as the scheduled RunScheduledSecurityUpdateJob, and
| records the attempt as a one-item ingestion batch so it appears in
| Pipeline Health like any scheduled scrape.
|
| A failed scrape is a normal outcome (returned, not thrown). Only
| securities without a scraper are rejected up front.
|
*/

class RunFundDataUpdateService
{
    public function __construct(
        private AiSecurityFundDataExtractionService $extractionService,
        private ProcessAiSecurityFundDataExtractionService $processService,
    ) {}

    /**
     * @return array{succeeded: bool, symbol: string, runtime_ms: int, data: ?array, error: ?string}
     *
     * @throws \DomainException when the security cannot be scraped
     */
    public function run(Security $security): array
    {
        $security->loadMissing('detail');

        if (! FundDataCoverage::hasScraper($security)) {

            throw new \DomainException(
                "{$security->symbol} cannot be updated: it is not active or its issuer has no fund data scraper."
            );
        }

        $startedAt = microtime(true);

        $batch = SecurityIngestionBatch::create([
            'batch_uuid' => Str::uuid()->toString(),
            'import_type_id' => ImportType::AI_DATA_EXTRACTION,
            'status_id' => Status::RUNNING,
            'total_securities' => 1,
            'processed_count' => 0,
            'success_count' => 0,
            'failure_count' => 0,
            'duplicate_count' => 0,
            'passed_data_integrity_check' => false,
            'processing_notes' => "Manual fund data update for {$security->symbol} from admin.",
            'started_at' => now(),
        ]);

        $item = SecurityIngestionBatchItem::create([
            'security_ingestion_batch_id' => $batch->id,
            'security_id' => $security->id,
            'security_update_type_id' => SecurityUpdateType::FUND_DATA,
            'status_id' => Status::PROCESSING,
            'attempts' => 1,
            'is_processed' => false,
            'is_success' => false,
            'started_at' => now(),
        ]);

        try {

            $extraction = $this->extractionService->extract($security);

            $extraction = $this->processService->process($extraction);

            $runtimeMs = $this->elapsed($startedAt);

            $item->update([
                'status_id' => Status::COMPLETED,
                'runtime_ms' => $runtimeMs,
                'is_processed' => true,
                'is_success' => true,
                'completed_at' => now(),
            ]);

            $batch->update([
                'status_id' => Status::COMPLETED,
                'processed_count' => 1,
                'success_count' => 1,
                'passed_data_integrity_check' => true,
                'completed_at' => now(),
            ]);

            $this->clearPublicCaches($security);

            $data = $extraction->extracted_data;

            return [
                'succeeded' => true,
                'symbol' => $security->symbol,
                'runtime_ms' => $runtimeMs,
                'data' => [
                    'assets_under_management' => $data['assets_under_management'] ?? null,
                    'aum_date' => $data['aum_date'] ?? null,
                    'nav_per_share' => $data['nav_per_share'] ?? null,
                    'nav_date' => $data['nav_date'] ?? null,
                ],
                'error' => null,
            ];
        } catch (Throwable $e) {

            $runtimeMs = $this->elapsed($startedAt);

            Log::warning('Manual fund data update failed', [
                'security_id' => $security->id,
                'symbol' => $security->symbol,
                'error' => $e->getMessage(),
            ]);

            $item->update([
                'status_id' => Status::FAILED,
                'runtime_ms' => $runtimeMs,
                'is_processed' => true,
                'is_success' => false,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $batch->update([
                'status_id' => Status::FAILED,
                'processed_count' => 1,
                'failure_count' => 1,
                'import_fail_details' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return [
                'succeeded' => false,
                'symbol' => $security->symbol,
                'runtime_ms' => $runtimeMs,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function elapsed(float $startedAt): int
    {
        return (int) ((microtime(true) - $startedAt) * 1000);
    }

    /**
     * New data should show on the public site immediately rather than
     * after the 10-minute cache expires.
     */
    private function clearPublicCaches(Security $security): void
    {
        Cache::forget(AumLeaderboardController::CACHE_KEY);
        Cache::forget(EtfSearchIndexController::CACHE_KEY);
        Cache::forget(AumHistoryController::cacheKey($security->symbol));
    }
}
