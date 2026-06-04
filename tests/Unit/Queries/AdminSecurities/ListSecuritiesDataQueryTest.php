<?php

namespace Tests\Unit\Queries\Admin\Securities;

use App\Models\DistributionFrequency;
use App\Models\EtfIssuer;
use App\Models\EtfStrategyType;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityType;
use App\Models\SecurityUpdateSchedule;
use App\Models\Status;
use App\Queries\Admin\Securities\ListSecuritiesDataQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListSecuritiesDataQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_update_schedules')->truncate();

        DB::table('security_details')->truncate();

        DB::table('securities')->truncate();

        DB::table('distribution_frequencies')->truncate();

        DB::table('etf_strategy_types')->truncate();

        DB::table('etf_issuers')->truncate();

        DB::table('security_types')->truncate();

        DB::table('statuses')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_update_schedules')->truncate();

        DB::table('security_details')->truncate();

        DB::table('securities')->truncate();

        DB::table('distribution_frequencies')->truncate();

        DB::table('etf_strategy_types')->truncate();

        DB::table('etf_issuers')->truncate();

        DB::table('security_types')->truncate();

        DB::table('statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_security_data()
    {
        $securityType =
            SecurityType::create([
                'security_type_name' => 'ETF',
            ]);

        $status =
            Status::create([
                'status_name' => 'Active',
            ]);

        $issuer =
            EtfIssuer::create([
                'etf_issuer_name' => 'YieldMax',
                'status_id' => $status->id,
            ]);

        $strategy =
            EtfStrategyType::create([
                'etf_strategy_type_name' => 'Covered Call',
            ]);

        $distribution =
            DistributionFrequency::create([
                'distribution_frequency_name' => 'Monthly',
            ]);

        $security =
            Security::create([
                'symbol' => 'CHPY',
                'security_type_id' => $securityType->id,
                'status_id' => $status->id,
            ]);

        SecurityDetail::create([
            'security_id' => $security->id,
            'security_name' => 'YieldMax Semiconductor Portfolio Option Income ETF',
            'etf_issuer_id' => $issuer->id,
            'etf_strategy_type_id' => $strategy->id,
            'distribution_frequency_id' => $distribution->id,
            'expense_ratio' => 0.99,
            'website_url' => 'https://yieldmaxetfs.com',
        ]);

        SecurityUpdateSchedule::create([
            'security_id' => $security->id,
            'security_update_type_id' => 1,
            'run_day' => 1,
            'run_hour' => 2,
            'status_id' => $status->id,
        ]);

        $results =
            app(
                ListSecuritiesDataQuery::class
            )->getData(

                new Request

            );

        $paginator =
            $results['data'];

        $this->assertEquals(
            1,
            $paginator->total()
        );

        $this->assertCount(
            1,
            $paginator->items()
        );

        $record =
            $paginator->items()[0];

        $this->assertEquals(
            'CHPY',
            $record['symbol']
        );

        $this->assertEquals(
            'YieldMax Semiconductor Portfolio Option Income ETF',
            $record['security_name']
        );

        $this->assertEquals(
            'YieldMax',
            $record['issuer']
        );

        $this->assertEquals(
            'Covered Call',
            $record['strategy']
        );

        $this->assertEquals(
            'Monthly',
            $record['distribution_frequency']
        );

        $this->assertEquals(
            1,
            $record['schedule_count']
        );

        $this->assertEquals(
            'ETF',
            $record['security_type']
        );

        $this->assertEquals(
            'Active',
            $record['status']
        );

        $this->assertArrayHasKey(

            'meta',

            $results

        );

        $this->assertArrayHasKey(

            'total_active',

            $results['meta']

        );

        $this->assertArrayHasKey(

            'total_retired',

            $results['meta']

        );
    }

    public function test_it_orders_by_symbol()
    {
        $securityType =
            SecurityType::create([
                'security_type_name' => 'ETF',
            ]);

        $status =
            Status::create([
                'status_name' => 'Active',
            ]);

        Security::create([
            'symbol' => 'ZZZZ',
            'security_type_id' => $securityType->id,
            'status_id' => $status->id,
        ]);

        Security::create([
            'symbol' => 'AAAA',
            'security_type_id' => $securityType->id,
            'status_id' => $status->id,
        ]);

        $results =
            app(
                ListSecuritiesDataQuery::class
            )->getData(

                new Request

            );

        $paginator =
            $results['data'];

        $this->assertEquals(
            'AAAA',
            $paginator->items()[0]['symbol']
        );
    }
}
