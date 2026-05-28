<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class TruncateTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:truncate-table {tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate one or more database tables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tables = collect(
            explode(',', $this->argument('tables'))
        )
            ->map(fn ($table) => trim($table))
            ->filter()
            ->values();

        if ($tables->isEmpty()) {

            $this->error('No tables were provided.');

            return self::FAILURE;
        }

        try {

            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($tables as $table) {

                if (! Schema::hasTable($table)) {
                    throw new InvalidArgumentException(
                        "Table [{$table}] does not exist."
                    );
                }

                DB::table($table)->truncate();

                $this->info("Successfully truncated table [{$table}].");
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Exception $e) {

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
