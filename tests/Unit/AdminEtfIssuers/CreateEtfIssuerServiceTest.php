<?php

namespace Tests\Unit\Services\Admin\EtfIssuers;

use App\Models\EtfIssuer;
use App\Models\Status;
use App\Services\Admin\EtfIssuers\CreateEtfIssuerService;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateEtfIssuerServiceTest extends TestCase
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

    public function test_it_creates_an_etf_issuer(): void
    {
        $issuer =
            app(
                CreateEtfIssuerService::class
            )->store([

                'etf_issuer_name' => 'YieldMax',

                'website_url' => 'https://yieldmaxetfs.com',

                'status_id' => Status::ACTIVE,

                'notes' => 'Covered call ETF issuer.',

            ]);

        $this->assertInstanceOf(
            EtfIssuer::class,
            $issuer
        );

        $this->assertDatabaseHas(
            'etf_issuers',
            [

                'etf_issuer_name' => 'YieldMax',

                'website_url' => 'https://yieldmaxetfs.com',

                'status_id' => Status::ACTIVE,

                'notes' => 'Covered call ETF issuer.',

            ]
        );

        $this->assertEquals(
            'YieldMax',
            $issuer->etf_issuer_name
        );

        $this->assertEquals(
            'https://yieldmaxetfs.com',
            $issuer->website_url
        );

        $this->assertEquals(
            Status::ACTIVE,
            $issuer->status_id
        );

        $this->assertEquals(
            'Covered call ETF issuer.',
            $issuer->notes
        );
    }

    public function test_it_can_create_an_issuer_without_optional_fields(): void
    {
        $issuer =
            app(
                CreateEtfIssuerService::class
            )->store([

                'etf_issuer_name' => 'Roundhill',

                'status_id' => Status::ACTIVE,

            ]);

        $this->assertDatabaseHas(
            'etf_issuers',
            [

                'etf_issuer_name' => 'Roundhill',

                'status_id' => Status::ACTIVE,

            ]
        );

        $this->assertNull(
            $issuer->website_url
        );

        $this->assertNull(
            $issuer->notes
        );
    }
}
