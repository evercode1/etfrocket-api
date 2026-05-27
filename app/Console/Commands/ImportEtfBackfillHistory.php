<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportEtfBackfillHistory extends Command
{
    protected $signature =

    'etfs:import-backfill-history

        {table : Database table to import into}

        {--chunk=1000 : Number of rows per chunk}';

    protected $description =

    'Import ETF backfill history records from CSV file.';

    private array $allowedTables = [

        'etf_price_histories',

        'etf_dividend_histories',

        'etf_nav_histories',

        'etf_aum_histories',

    ];

    public function handle(): int
    {
        ini_set('memory_limit', '-1');

        set_time_limit(0);

        $table =
            $this->argument(
                'table'
            );

        $chunkSize =
            (int) $this->option(
                'chunk'
            );

        /*
        |--------------------------------------------------------------------------
        | Validate Table
        |--------------------------------------------------------------------------
        */

        if (

            ! in_array(

                $table,

                $this->allowedTables

            )

        ) {

            $this->error(
                'Invalid import table.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Build File Path
        |--------------------------------------------------------------------------
        */

        $file =

            config('imports.path')

            . '/'

            . $table

            . '.csv';

        /*
        |--------------------------------------------------------------------------
        | Validate File
        |--------------------------------------------------------------------------
        */

        if (! file_exists($file)) {

            $this->error(
                "CSV file not found: {$file}"
            );

            return self::FAILURE;
        }

        $handle =
            fopen($file, 'r');

        if (! $handle) {

            $this->error(
                'Unable to open CSV file.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Header Row
        |--------------------------------------------------------------------------
        */

        $header =
            fgetcsv($handle);

        if (! $header) {

            $this->error(
                'CSV header row missing.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Truncate Table
        |--------------------------------------------------------------------------
        */

        $this->warn(
            "Truncating {$table}..."
        );

        DB::table($table)
            ->truncate();

        /*
        |--------------------------------------------------------------------------
        | Import
        |--------------------------------------------------------------------------
        */

        $rows = [];

        $processed = 0;

        while (($data = fgetcsv($handle)) !== false) {

            $row =
                array_combine(
                    $header,
                    $data
                );

            $formatted = [];

            foreach (
                $row as $key => $value
            ) {

                if ($value === '') {

                    $formatted[$key] = null;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Dates
                |--------------------------------------------------------------------------
                */

                if (

                    str_contains($key, 'date')

                    ||

                    str_contains($key, '_at')

                ) {

                    try {

                        $formatted[$key] =

                            Carbon::parse(
                                $value
                            );
                    } catch (\Throwable $e) {

                        $formatted[$key] = null;
                    }

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Numeric
                |--------------------------------------------------------------------------
                */

                if (is_numeric($value)) {

                    $formatted[$key] =

                        str_contains($value, '.')

                        ? (float) $value

                        : (int) $value;

                    continue;
                }

                $formatted[$key] = $value;
            }

            $formatted['created_at'] =
                now();

            $formatted['updated_at'] =
                now();

            $rows[] = $formatted;

            /*
            |--------------------------------------------------------------------------
            | Chunk Insert
            |--------------------------------------------------------------------------
            */

            if (

                count($rows)
                >=
                $chunkSize

            ) {

                DB::table($table)
                    ->insert($rows);

                $processed +=
                    count($rows);

                $this->info(
                    "Processed {$processed} rows..."
                );

                $rows = [];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final Chunk
        |--------------------------------------------------------------------------
        */

        if (! empty($rows)) {

            DB::table($table)
                ->insert($rows);

            $processed +=
                count($rows);
        }

        fclose($handle);

        /*
        |--------------------------------------------------------------------------
        | Complete
        |--------------------------------------------------------------------------
        */

        $this->info(
            "Import complete."
        );

        $this->info(
            "Total rows processed: {$processed}"
        );

        return self::SUCCESS;
    }
}
