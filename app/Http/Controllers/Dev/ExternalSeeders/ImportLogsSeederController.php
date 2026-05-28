<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\DataSource;
use App\Models\ImportLog;
use App\Models\ImportType;
use App\Models\Status;

class ImportLogsSeederController extends Controller
{
    public function run(): void
    {

        ImportLog::truncate();

        ImportLog::create([

            'import_type_id' => ImportType::MARKET_SNAPSHOT,

            'status_id' => Status::COMPLETED,

            'data_source_id' => DataSource::AI_SCRAPER,

            'run_time' => 8,

            'rows_processed' => 1200,

            'records_created' => 900,

            'records_updated' => 300,

            'duplicate_rows' => 0,

            'failure_count' => 0,

            'passed_data_integrity_check' => 1,

            'generated_markdown' => '# AI Market Snapshot',

            'processing_notes' => 'Market snapshot generated successfully.',

            'import_fail_details' => null,

            'started_at' => now()->subMinutes(30),

            'completed_at' => now()->subMinutes(29),

        ]);

        ImportLog::create([

            'import_type_id' => ImportType::ETF_PRICE_IMPORT,

            'status_id' => Status::FAILED,

            'data_source_id' => DataSource::TIINGO_API,

            'run_time' => 5,

            'rows_processed' => 400,

            'records_created' => 100,

            'records_updated' => 250,

            'duplicate_rows' => 15,

            'failure_count' => 2,

            'passed_data_integrity_check' => 0,

            'generated_markdown' => null,

            'processing_notes' => 'Provider timeout occurred during synchronization.',

            'import_fail_details' => 'Tiingo API timeout while processing ETF prices.',

            'started_at' => now()->subMinutes(20),

            'completed_at' => now()->subMinutes(19),

        ]);

        ImportLog::create([

            'import_type_id' => ImportType::CALCULATE_ETF_METRICS,

            'status_id' => Status::COMPLETED,

            'data_source_id' => DataSource::MANUAL_ENTRY,

            'run_time' => 14,

            'rows_processed' => 2200,

            'records_created' => 1600,

            'records_updated' => 600,

            'duplicate_rows' => 0,

            'failure_count' => 0,

            'passed_data_integrity_check' => 1,

            'generated_markdown' => '# ETF Metrics Calculation Complete',

            'processing_notes' => 'ETF metrics calculated successfully.',

            'import_fail_details' => null,

            'started_at' => now()->subMinutes(10),

            'completed_at' => now()->subMinutes(9),

        ]);
    }
}
