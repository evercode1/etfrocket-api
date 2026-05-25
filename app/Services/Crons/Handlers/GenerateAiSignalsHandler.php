<?php

namespace App\Services\Crons\Handlers;

use App\Models\AiMarketSignal;

use App\Models\ImportType;

use App\Models\SignalType;

use App\Models\Status;

use App\Services\AI\AiSignals\GenerateAiSignalService;

use App\Services\AI\AiSignals\IsMarketOpenService;

use App\Services\ImportLogs\ImportLogsService;

use Throwable;

class GenerateAiSignalsHandler

{

    public function __construct(

        private GenerateAiSignalService

        $generateAiSignalService,

        private IsMarketOpenService

        $isMarketOpenService

    ) {}

    // we ignore payload, but we need it for dynamic method calling in CronService

    public function handleGenerateAiSignals(

        array $payload = []

    ): array {

        $startedAt = now();

        try {

            $force =

                $payload['force']

                ?? false;

            /*

            |--------------------------------------------------------------------------

            | Market Open Check

            |--------------------------------------------------------------------------

            */

            if (

                ! $force &&

                ! $this->isMarketOpenService

                    ->isOpen()

            ) {

                $message =

                    'Skipped AI signal generation. Market is currently closed.';

                ImportLogsService::log(

                    import_type_id: ImportType::MARKET_SNAPSHOT,

                    status_id: Status::COMPLETED,

                    run_time: $startedAt->diffInSeconds(

                        now()

                    ),

                    rows_processed: 0,

                    records_created: 0,

                    records_updated: 0,

                    duplicate_rows: 0,

                    failure_count: 0,

                    passed_data_integrity_check: true,

                    processing_notes: $message,

                    started_at: $startedAt,

                    completed_at: now(),

                );

                return [

                    'success' => 1,

                    'cron_fail_details' => null,

                ];
            }

            /*

            |--------------------------------------------------------------------------

            | Freshness Check

            |--------------------------------------------------------------------------

            */

            $latestSignalDate =

                optional(

                    AiMarketSignal::latest(

                        'generated_at'

                    )->first()

                )?->generated_at?->toDateString();

            $today =

                now()->toDateString();

            if (

                ! $force &&

                $latestSignalDate ===

                $today

            ) {

                $message =

                    'Skipped AI signal generation. Signals already generated today.';

                ImportLogsService::log(

                    import_type_id: ImportType::MARKET_SNAPSHOT,

                    status_id: Status::COMPLETED,

                    run_time: $startedAt->diffInSeconds(

                        now()

                    ),

                    rows_processed: 0,

                    records_created: 0,

                    records_updated: 0,

                    duplicate_rows: 0,

                    failure_count: 0,

                    passed_data_integrity_check: true,

                    processing_notes: $message,

                    started_at: $startedAt,

                    completed_at: now(),

                );

                return [

                    'success' => 1,

                    'cron_fail_details' => null,

                ];
            }

            /*

            |--------------------------------------------------------------------------

            | Signal Types

            |--------------------------------------------------------------------------

            */

            $signalTypes = [

                SignalType::MARKET_SNAPSHOT =>

                ImportType::MARKET_SNAPSHOT,

                SignalType::MARKET_CONDITIONS =>

                ImportType::MARKET_CONDITIONS,

                SignalType::MARKET_EVENTS =>

                ImportType::MARKET_EVENTS,

            ];

            /*

            |--------------------------------------------------------------------------

            | Generate Signals

            |--------------------------------------------------------------------------

            */

            foreach (

                $signalTypes as

                $signalTypeId =>

                $importTypeId

            ) {

                $signalStartedAt =

                    now();

                try {

                    $signal =

                        $this->generateAiSignalService

                        ->generate(

                            $signalTypeId

                        );

                    ImportLogsService::log(

                        import_type_id: $importTypeId,

                        status_id: Status::COMPLETED,

                        run_time: $signalStartedAt

                            ->diffInSeconds(

                                now()

                            ),

                        rows_processed: 1,

                        records_created: 1,

                        records_updated: 0,

                        duplicate_rows: 0,

                        failure_count: 0,

                        passed_data_integrity_check: true,

                        generated_markdown: $signal->markdown_content,

                        processing_notes: $force

                            ? 'Forced AI signal generation executed successfully.'

                            : 'AI signal generated successfully.',

                        started_at: $signalStartedAt,

                        completed_at: now(),

                    );
                } catch (Throwable $e) {

                    ImportLogsService::log(

                        import_type_id: $importTypeId,

                        status_id: Status::FAILED,

                        run_time: $signalStartedAt

                            ->diffInSeconds(

                                now()

                            ),

                        rows_processed: 1,

                        records_created: 0,

                        records_updated: 0,

                        duplicate_rows: 0,

                        failure_count: 1,

                        passed_data_integrity_check: false,

                        processing_notes: 'AI signal generation failed.',

                        import_fail_details: $e->getMessage(),

                        started_at: $signalStartedAt,

                        completed_at: now(),

                    );
                }
            }

            return [

                'success' => 1,

                'cron_fail_details' => null,

            ];
        } catch (Throwable $e) {

            report($e);

            return [

                'success' => 0,

                'cron_fail_details' =>

                $this->errorMessage() .

                    $e->getMessage(),

            ];
        }
    }

    public function errorMessage(): string

    {

        return

            'AI signal generation failed. ';
    }
}
