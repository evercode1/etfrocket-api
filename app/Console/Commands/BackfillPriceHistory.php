<?php

namespace App\Console\Commands;

use App\Models\Security;
use App\Models\SecurityPriceHistory;
use App\Services\Imports\ImportSecurityPriceHistoryService;
use Illuminate\Console\Command;

class BackfillPriceHistory extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature =

        'securities:backfill-price-history

        {symbol? : Optional security symbol}

        {--chunk=25 : Number of securities to process per chunk}';

    /**
     * The console command description.
     */
    protected $description =

        'Backfill security price history from CSV import files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        ini_set(
            'memory_limit',
            '-1'
        );

        set_time_limit(
            0
        );

        $symbol =

            $this->argument(
                'symbol'
            );

        /*
        |--------------------------------------------------------------------------
        | Single Security Mode
        |--------------------------------------------------------------------------
        */

        if ($symbol) {

            $security =

                Security::where(

                    'symbol',

                    strtoupper(
                        trim($symbol)
                    )

                )->first();

            if (! $security) {

                $this->error(

                    "Security with symbol [{$symbol}] was not found."

                );

                return self::FAILURE;
            }

            return $this->processSecurity(
                $security
            );
        }

        /*
        |--------------------------------------------------------------------------
        | All Securities Mode
        |--------------------------------------------------------------------------
        */

        $chunkSize =

            (int) $this->option(
                'chunk'
            );

        $processed = 0;

        $successful = 0;

        $failed = 0;

        $total =

            Security::count();

        $this->info(

            "Processing {$total} securities..."

        );

        Security::orderBy(
            'symbol'
        )

            ->chunk(

                $chunkSize,

                function ($securities) use (

                    &$processed,

                    &$successful,

                    &$failed

                ) {

                    foreach (

                        $securities as $security

                    ) {

                        $processed++;

                        $this->line(

                            "[{$processed}] {$security->symbol}"

                        );

                        $result =

                            $this->processSecurity(
                                $security,
                                false
                            );

                        if (

                            $result ===
                            self::SUCCESS

                        ) {

                            $successful++;

                        } else {

                            $failed++;
                        }
                    }
                }

            );

        $this->newLine();

        $this->info(
            'Completed.'
        );

        $this->info(
            "Processed: {$processed}"
        );

        $this->info(
            "Successful: {$successful}"
        );

        $this->info(
            "Failed: {$failed}"
        );

        return self::SUCCESS;
    }

    private function processSecurity(

        Security $security,

        bool $showTable = true

    ): int {

        $countBefore =

            SecurityPriceHistory::where(

                'security_id',

                $security->id

            )->count();

        if ($showTable) {

            $this->info(

                "Rows before import: {$countBefore}"

            );
        }

        $filePath =

            app_path(

                'Imports/PriceData/'.

                strtolower(
                    $security->symbol
                ).

                '.txt'

            );

        if (! file_exists($filePath)) {

            $this->warn(

                "Import file not found for {$security->symbol}"

            );

            return self::FAILURE;
        }

        try {

            $results =

                app(

                    ImportSecurityPriceHistoryService::class

                )
                    ->import(

                        $security->id,

                        $filePath

                    );

        } catch (\Throwable $e) {

            $this->error(

                "{$security->symbol}: ".

                $e->getMessage()

            );

            return self::FAILURE;
        }

        if ($showTable) {

            $this->info(

                "Successfully imported security history for {$results['symbol']}."

            );

            $this->table([

                'Security ID',

                'Symbol',

                'Price Rows Imported',

                'Price Rows Deleted',

                'Dividend Rows Imported',

                'Dividend Rows Deleted',

                'Start Date',

                'End Date',

            ], [[

                $results['security_id'],

                $results['symbol'],

                $results['rows_imported'],

                $results['rows_deleted'],

                $results['dividend_rows_imported'],

                $results['dividend_rows_deleted'],

                $results['start_date'],

                $results['end_date'],

            ]]);

        } else {

            $this->line(

                "✓ {$results['symbol']} | ".

                "{$results['rows_imported']} prices | ".

                "{$results['dividend_rows_imported']} dividends"

            );
        }

        return self::SUCCESS;
    }
}
