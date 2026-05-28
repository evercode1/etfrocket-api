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
     *
     * @var string
     */
    protected $signature = 'securities:backfill-price-history {symbol}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill security price history from a CSV import file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $symbol = strtoupper(
            trim($this->argument('symbol'))
        );

        $security = Security::where('symbol', $symbol)
            ->first();

        if (! $security) {

            $this->error("Security with symbol [{$symbol}] was not found.");

            return self::FAILURE;
        }

        $countBefore = SecurityPriceHistory::where('security_id', $security->id)->count();

        $this->info("Rows before import: {$countBefore}");

        $filePath = app_path('Imports/PriceData/'.strtolower($symbol).'.txt');

        if (! file_exists($filePath)) {

            $this->error("Import file not found at [{$filePath}].");

            return self::FAILURE;
        }

        try {

            $results = (new ImportSecurityPriceHistoryService)->import(
                $security->id,
                $filePath
            );
        } catch (\Exception $e) {

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Successfully imported security history for {$results['symbol']}.");

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

        return self::SUCCESS;
    }
}
