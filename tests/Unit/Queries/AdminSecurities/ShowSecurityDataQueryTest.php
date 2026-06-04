<?php

namespace Tests\Unit\Queries\Admin\Securities;

use App\Models\DistributionFrequency;
use App\Models\EtfIssuer;
use App\Models\EtfStrategyType;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityType;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Queries\Admin\Securities\ShowSecurityDataQuery;
use Database\Seeders\StatusSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShowSecurityDataQueryTest extends TestCase
{
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

        $this->seed(
            StatusSeeder::class
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

    public function test_it_returns_security_with_relationships(): void
    {

        $securityType =
            SecurityType::create([

                'security_type_name' => 'ETF',

            ]);

        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'status_id' => Status::ACTIVE,

            ]);

        $strategy =
            EtfStrategyType::create([

                'etf_strategy_type_name' => 'Option Income',

            ]);

        $distribution =
            DistributionFrequency::create([

                'distribution_frequency_name' => 'Monthly',

            ]);

        $updateType =
            SecurityUpdateType::create([

                'security_update_type_name' => 'Dividend History',

            ]);

        $security =
            Security::create([

                'symbol' => 'CHPY',

                'security_type_id' => $securityType->id,

                'status_id' => Status::ACTIVE,

            ]);

        $detail =
            SecurityDetail::create([

                'security_id' => $security->id,

                'security_name' => 'YieldMax Semiconductor Portfolio Option Income ETF',

                'etf_issuer_id' => $issuer->id,

                'etf_strategy_type_id' => $strategy->id,

                'distribution_frequency_id' => $distribution->id,

                'expense_ratio' => 0.99,

                'website_url' => 'https://www.yieldmaxetfs.com',

                'notes' => 'Test notes',

            ]);

        $schedule =
            SecurityUpdateSchedule::create([

                'security_id' => $security->id,

                'security_update_type_id' => $updateType->id,

                'run_day' => 5,

                'run_hour' => 4,

                'status_id' => Status::ACTIVE,

            ]);

        $result =
            app(
                ShowSecurityDataQuery::class
            )->getData(
                $security->id
            );

        $this->assertEquals(
            $security->id,
            $result->id
        );

        $this->assertEquals(
            'CHPY',
            $result->symbol
        );

        $this->assertNotNull(
            $result->detail
        );

        $this->assertEquals(
            $detail->security_name,
            $result->detail->security_name
        );

        $this->assertEquals(
            'YieldMax',
            $result->detail
                ->issuer
                ->etf_issuer_name
        );

        $this->assertEquals(
            'Option Income',
            $result->detail
                ->strategyType
                ->etf_strategy_type_name
        );

        $this->assertEquals(
            'Monthly',
            $result->detail
                ->distributionFrequency
                ->distribution_frequency_name
        );

        $this->assertEquals(
            'ETF',
            $result->securityType
                ->security_type_name
        );

        $this->assertEquals(
            'Active',
            $result->status
                ->status_name
        );

        $this->assertCount(
            1,
            $result->updateSchedules
        );

        $this->assertEquals(
            5,
            $result->updateSchedules
                ->first()
                ->run_day
        );

        $this->assertEquals(
            4,
            $result->updateSchedules
                ->first()
                ->run_hour
        );

        $this->assertEquals(
            'Dividend History',
            $result->updateSchedules
                ->first()
                ->updateType
                ->security_update_type_name
        );
    }

    public function test_it_throws_exception_for_invalid_security(): void
    {
        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            ShowSecurityDataQuery::class
        )->getData(
            999999
        );
    }
}
