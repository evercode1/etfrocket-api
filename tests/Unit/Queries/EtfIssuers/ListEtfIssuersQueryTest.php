<?php

namespace Tests\Unit\Queries\Admin\EtfIssuers;

use App\Models\EtfIssuer;
use App\Models\Status;
use App\Queries\Admin\EtfIssuers\ListEtfIssuersQuery;
use Database\Seeders\StatusSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListEtfIssuersQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_issuers')->truncate();

        DB::table('statuses')->truncate();

        $this->seed(
            StatusSeeder::class
        );
    }

    protected function tearDown(): void
    {
        DB::table('etf_issuers')->truncate();

        DB::table('statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_issuer_data(): void
    {
        EtfIssuer::create([

            'etf_issuer_name' => 'YieldMax',

            'website_url' => 'https://yieldmaxetfs.com',

            'status_id' => Status::ACTIVE,

            'notes' => 'Covered call ETF issuer.',

        ]);

        $results =
            app(
                ListEtfIssuersQuery::class
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
            'YieldMax',
            $record['etf_issuer_name']
        );

        $this->assertEquals(
            'https://yieldmaxetfs.com',
            $record['website_url']
        );

        $this->assertEquals(
            'Active',
            $record['status']
        );

        $this->assertArrayHasKey(
            'updated_at',
            $record
        );

        $this->assertEquals(
            1,
            $results['meta']['total_active']
        );

        $this->assertEquals(
            0,
            $results['meta']['total_retired']
        );
    }

    public function test_it_orders_by_issuer_name(): void
    {
        EtfIssuer::create([

            'etf_issuer_name' => 'ZZZ Issuer',

            'website_url' => 'https://zzz.example.com',

            'status_id' => Status::ACTIVE,

        ]);

        EtfIssuer::create([

            'etf_issuer_name' => 'AAA Issuer',

            'website_url' => 'https://aaa.example.com',

            'status_id' => Status::ACTIVE,

        ]);

        $results =
            app(
                ListEtfIssuersQuery::class
            )->getData(
                new Request
            );

        $paginator =
            $results['data'];

        $this->assertEquals(
            'AAA Issuer',
            $paginator->items()[0]['etf_issuer_name']
        );
    }

    public function test_it_can_search_by_issuer_name(): void
    {
        EtfIssuer::create([

            'etf_issuer_name' => 'YieldMax',

            'website_url' => 'https://yieldmaxetfs.com',

            'status_id' => Status::ACTIVE,

        ]);

        EtfIssuer::create([

            'etf_issuer_name' => 'Roundhill',

            'website_url' => 'https://roundhillinvestments.com',

            'status_id' => Status::ACTIVE,

        ]);

        $request =
            new Request([

                'search' => 'YieldMax',

            ]);

        $results =
            app(
                ListEtfIssuersQuery::class
            )->getData(
                $request
            );

        $paginator =
            $results['data'];

        $this->assertEquals(
            1,
            $paginator->total()
        );

        $this->assertEquals(
            'YieldMax',
            $paginator->items()[0]['etf_issuer_name']
        );
    }

    public function test_it_can_filter_by_status(): void
    {
        EtfIssuer::create([

            'etf_issuer_name' => 'Active Issuer',

            'website_url' => 'https://active.example.com',

            'status_id' => Status::ACTIVE,

        ]);

        EtfIssuer::create([

            'etf_issuer_name' => 'Retired Issuer',

            'website_url' => 'https://retired.example.com',

            'status_id' => Status::RETIRED,

        ]);

        $request =
            new Request([

                'status_id' => Status::RETIRED,

            ]);

        $results =
            app(
                ListEtfIssuersQuery::class
            )->getData(
                $request
            );

        $paginator =
            $results['data'];

        $this->assertEquals(
            1,
            $paginator->total()
        );

        $this->assertEquals(
            'Retired Issuer',
            $paginator->items()[0]['etf_issuer_name']
        );
    }

    public function test_it_honors_per_page_parameter(): void
    {
        for ($i = 1; $i <= 30; $i++) {

            EtfIssuer::create([

                'etf_issuer_name' => 'Issuer '.$i,

                'website_url' => 'https://issuer'.$i.'.example.com',

                'status_id' => Status::ACTIVE,

            ]);
        }

        $request =
            new Request([

                'per_page' => 10,

            ]);

        $results =
            app(
                ListEtfIssuersQuery::class
            )->getData(
                $request
            );

        $paginator =
            $results['data'];

        $this->assertEquals(
            30,
            $paginator->total()
        );

        $this->assertCount(
            10,
            $paginator->items()
        );

        $this->assertEquals(
            10,
            $paginator->perPage()
        );
    }
}
