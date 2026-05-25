<?php

namespace App\Queries\ImportLogs;

use App\Models\ImportLog;

class ShowImportLogQuery
{
    public function getData(
        int $id
    ) {

        return ImportLog::select([

            'import_logs.id',

            'import_logs.run_time',

            'import_logs.rows_processed',

            'import_logs.records_created',

            'import_logs.records_updated',

            'import_logs.duplicate_rows',

            'import_logs.failure_count',

            'import_logs.generated_markdown',

            'import_logs.processing_notes',

            'import_logs.import_fail_details',

            'import_logs.passed_data_integrity_check',

            'import_logs.started_at',

            'import_logs.completed_at',

            'import_types.import_type_name',

            'statuses.status_name',

            'data_sources.data_source_name',

        ])

            ->leftJoin(

                'import_types',

                'import_logs.import_type_id',

                '=',

                'import_types.id'

            )

            ->leftJoin(

                'statuses',

                'import_logs.status_id',

                '=',

                'statuses.id'

            )

            ->leftJoin(

                'data_sources',

                'import_logs.data_source_id',

                '=',

                'data_sources.id'

            )

            ->where(
                'import_logs.id',
                $id
            )

            ->firstOrFail();
    }
}
