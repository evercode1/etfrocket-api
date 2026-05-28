<?php

namespace App\Console\Commands;

use App\Models\Etf;
use App\Models\SecurityType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MoveEtfSeedsToSecurities extends Command
{
    protected $signature =

        'dev:move-etf-seeds-to-securities';

    protected $description =

        'Generate security seeders from ETF records.';

    public function handle(): int
    {
        $etfs =

            Etf::query()
                ->orderBy('symbol')
                ->get();

        if ($etfs->isEmpty()) {

            $this->error(
                'No ETF records found.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Securities Array
        |--------------------------------------------------------------------------
        */

        $securities = [];

        $securityDetails = [];

        $securityId = 1;

        foreach ($etfs as $etf) {

            $securities[] = [

                'id' => $securityId,

                'symbol' => $etf->symbol,

                'security_type_id' => SecurityType::ETF,

                'status_id' => $etf->status_id,

            ];

            $securityDetails[] = [

                'security_id' => $securityId,

                'security_name' => $etf->fund_name,

                'etf_issuer_id' => $etf->etf_issuer_id,

                'etf_strategy_type_id' => $etf->etf_strategy_type_id,

                'distribution_frequency_id' => $etf->distribution_frequency_id,

                'expense_ratio' => $etf->expense_ratio,

                'inception_date' => optional(
                    $etf->inception_date
                )?->toDateString(),

                'source' => $etf->source,

                'website_url' => $etf->website_url,

                'notes' => $etf->notes,

            ];

            $securityId++;
        }

        /*
        |--------------------------------------------------------------------------
        | Export Arrays
        |--------------------------------------------------------------------------
        */

        $securitiesExport =
            $this->formatArray(
                $securities
            );

        $securityDetailsExport =
            $this->formatArray(
                $securityDetails
            );

        /*
        |--------------------------------------------------------------------------
        | Securities Seeder Controller
        |--------------------------------------------------------------------------
        */

        $securitiesController = <<<PHP
<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\Security;
use Illuminate\Support\Facades\DB;

class SecuritiesSeederController extends Controller
{
    public function run(): void
    {
        DB::table('securities')
            ->truncate();

        \$now = now();

        \$securities = {$securitiesExport};

        \$securities = array_map(

            function (\$security) use (\$now) {

                \$security['created_at'] =
                    \$now;

                \$security['updated_at'] =
                    \$now;

                return \$security;
            },

            \$securities

        );

        Security::insert(
            \$securities
        );
    }
}
PHP;

        /*
        |--------------------------------------------------------------------------
        | Security Details Seeder Controller
        |--------------------------------------------------------------------------
        */

        $securityDetailsController = <<<PHP
<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\SecurityDetail;
use Illuminate\Support\Facades\DB;

class SecurityDetailsSeederController extends Controller
{
    public function run(): void
    {
        DB::table('security_details')
            ->truncate();

        \$now = now();

        \$details = {$securityDetailsExport};

        \$details = array_map(

            function (\$detail) use (\$now) {

                \$detail['created_at'] =
                    \$now;

                \$detail['updated_at'] =
                    \$now;

                return \$detail;
            },

            \$details

        );

        SecurityDetail::insert(
            \$details
        );
    }
}
PHP;

        /*
        |--------------------------------------------------------------------------
        | Securities Seeder
        |--------------------------------------------------------------------------
        */

        $securitiesSeeder = <<<PHP
<?php

namespace Database\Seeders;

use App\Http\Controllers\Dev\ExternalSeeders\SecuritiesSeederController;
use Illuminate\Database\Seeder;

class SecuritiesSeeder extends Seeder
{
    public function run(): void
    {
        app(
            SecuritiesSeederController::class
        )->run();
    }
}
PHP;

        /*
        |--------------------------------------------------------------------------
        | Security Details Seeder
        |--------------------------------------------------------------------------
        */

        $securityDetailsSeeder = <<<PHP
<?php

namespace Database\Seeders;

use App\Http\Controllers\Dev\ExternalSeeders\SecurityDetailsSeederController;
use Illuminate\Database\Seeder;

class SecurityDetailsSeeder extends Seeder
{
    public function run(): void
    {
        app(
            SecurityDetailsSeederController::class
        )->run();
    }
}
PHP;

        /*
        |--------------------------------------------------------------------------
        | Ensure Directories Exist
        |--------------------------------------------------------------------------
        */

        File::ensureDirectoryExists(

            app_path(
                'Http/Controllers/Dev/ExternalSeeders'
            )

        );

        File::ensureDirectoryExists(

            database_path(
                'seeders'
            )

        );

        /*
        |--------------------------------------------------------------------------
        | Write Controller Files
        |--------------------------------------------------------------------------
        */

        File::put(

            app_path(
                'Http/Controllers/Dev/ExternalSeeders/SecuritiesSeederController.php'
            ),

            $securitiesController

        );

        File::put(

            app_path(
                'Http/Controllers/Dev/ExternalSeeders/SecurityDetailsSeederController.php'
            ),

            $securityDetailsController

        );

        /*
        |--------------------------------------------------------------------------
        | Write Seeder Files
        |--------------------------------------------------------------------------
        */

        File::put(

            database_path(
                'seeders/SecuritiesSeeder.php'
            ),

            $securitiesSeeder

        );

        File::put(

            database_path(
                'seeders/SecurityDetailsSeeder.php'
            ),

            $securityDetailsSeeder

        );

        $this->info(
            'Security seeders generated successfully.'
        );

        return self::SUCCESS;
    }

    private function formatArray(
        array $rows
    ): string {

        $output = "[\n";

        foreach ($rows as $row) {

            $output .= "    [\n";

            foreach ($row as $key => $value) {

                $formattedValue =

                    match (true) {

                        is_null($value) => 'null',

                        is_numeric($value) => $value,

                        default => "'".addslashes($value)."'",

                    };

                $output .=

                    "        '{$key}' => {$formattedValue},\n";
            }

            $output .= "    ],\n";
        }

        $output .= ']';

        return $output;
    }
}
