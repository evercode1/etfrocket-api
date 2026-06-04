<?php

namespace Tests\Unit\Services\Admin\Securities;

use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityType;
use App\Models\Status;
use App\Services\Admin\Securities\CreateSecurityService;
use Database\Seeders\DistributionFrequencySeeder;
use Database\Seeders\EtfStrategyTypeSeeder;
use Database\Seeders\SecurityTypeSeeder;
use Database\Seeders\SecurityUpdateTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateSecurityServiceTest extends TestCase
{
    private CreateSecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_update_schedules')->truncate();

        DB::table('security_details')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_update_types')->truncate();

        DB::table('distribution_frequencies')->truncate();

        DB::table('etf_strategy_types')->truncate();

        DB::table('etf_issuers')->truncate();

        DB::table('security_types')->truncate();

        DB::table('statuses')->truncate();

        $this->seed([

            StatusSeeder::class,

            SecurityTypeSeeder::class,

            EtfStrategyTypeSeeder::class,

            DistributionFrequencySeeder::class,

            SecurityUpdateTypeSeeder::class,

        ]);

        $this->service =
            app(
                CreateSecurityService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_update_schedules')->truncate();

        DB::table('security_details')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_update_types')->truncate();

        DB::table('distribution_frequencies')->truncate();

        DB::table('etf_strategy_types')->truncate();

        DB::table('etf_issuers')->truncate();

        DB::table('security_types')->truncate();

        DB::table('statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_creates_security_with_related_records(): void
    {
        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'status_id' => Status::ACTIVE,

            ]);

        $data = [

            'symbol' => 'CHPY',

            'security_type_id' => SecurityType::ETF,

            'status_id' => Status::ACTIVE,

            'security_name' => 'YieldMax Semiconductor Portfolio Option Income ETF',

            'etf_issuer_id' => $issuer->id,

            'etf_strategy_type_id' => 1,

            'distribution_frequency_id' => 1,

            'expense_ratio' => 0.99,

            'website_url' => 'https://www.yieldmaxetfs.com',

            'notes' => 'Test security.',

            'schedules' => [

                [

                    'security_update_type_id' => 1,

                    'run_day' => 5,

                    'run_hour' => 4,

                    'status_id' => Status::ACTIVE,

                ],

                [

                    'security_update_type_id' => 2,

                    'run_day' => 1,

                    'run_hour' => 5,

                    'status_id' => Status::ACTIVE,

                ],

            ],

        ];

        $security =
            $this->service
                ->store(
                    $data
                );

        $this->assertInstanceOf(

            Security::class,

            $security

        );

        $this->assertDatabaseHas(

            'securities',

            [

                'symbol' => 'CHPY',

                'status_id' => Status::ACTIVE,

            ]

        );

        $this->assertDatabaseHas(

            'security_details',

            [

                'security_id' => $security->id,

                'security_name' => 'YieldMax Semiconductor Portfolio Option Income ETF',

                'etf_issuer_id' => $issuer->id,

            ]

        );

        $this->assertDatabaseCount(

            'security_update_schedules',

            2

        );

        $this->assertDatabaseHas(

            'security_update_schedules',

            [

                'security_id' => $security->id,

                'security_update_type_id' => 1,

                'run_day' => 5,

                'run_hour' => 4,

            ]

        );

        $this->assertDatabaseHas(

            'security_update_schedules',

            [

                'security_id' => $security->id,

                'security_update_type_id' => 2,

                'run_day' => 1,

                'run_hour' => 5,

            ]

        );
    }
}
