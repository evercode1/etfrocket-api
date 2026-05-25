<?php

namespace App\Services\ImportLogs;

use App\Models\ImportLog;

class ImportLogsService
{
    public static function log(

        int $import_type_id,

        int $status_id,

        ?int $data_source_id = null,

        int $run_time = 0,

        int $rows_processed = 0,

        int $records_created = 0,

        int $records_updated = 0,

        int $duplicate_rows = 0,

        int $failure_count = 0,

        bool $passed_data_integrity_check = false,

        ?string $generated_markdown = null,

        ?string $processing_notes = null,

        ?string $import_fail_details = null,

        ?string $started_at = null,

        ?string $completed_at = null,

    ): ImportLog {

        return ImportLog::create([

            'import_type_id' =>

            $import_type_id,

            'status_id' =>

            $status_id,

            'data_source_id' =>

            $data_source_id,

            'run_time' =>

            $run_time,

            'rows_processed' =>

            $rows_processed,

            'records_created' =>

            $records_created,

            'records_updated' =>

            $records_updated,

            'duplicate_rows' =>

            $duplicate_rows,

            'failure_count' =>

            $failure_count,

            'passed_data_integrity_check' =>

            $passed_data_integrity_check,

            'generated_markdown' =>

            $generated_markdown,

            'processing_notes' =>

            $processing_notes,

            'import_fail_details' =>

            $import_fail_details,

            'started_at' =>

            $started_at ?? now(),

            'completed_at' =>

            $completed_at ?? now(),

        ]);
    }
}
