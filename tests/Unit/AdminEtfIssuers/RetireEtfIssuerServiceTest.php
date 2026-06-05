<?php

namespace Tests\Unit\Services\Admin\EtfIssuers;

use App\Models\EtfIssuer;
use App\Models\Status;
use App\Services\Admin\EtfIssuers\RetireEtfIssuerService;
use Database\Seeders\StatusSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RetireEtfIssuerServiceTest extends TestCase
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

    public function test_it_retires_an_etf_issuer(): void
    {
        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'website_url' => 'https://yieldmaxetfs.com',

                'status_id' => Status::ACTIVE,

                'notes' => 'Covered call ETF issuer.',

            ]);

        $retiredIssuer =
            app(
                RetireEtfIssuerService::class
            )->retire(
                $issuer->id
            );

        $this->assertInstanceOf(
            EtfIssuer::class,
            $retiredIssuer
        );

        $this->assertEquals(
            $issuer->id,
            $retiredIssuer->id
        );

        $this->assertEquals(
            Status::RETIRED,
            $retiredIssuer->status_id
        );

        $this->assertDatabaseHas(

            'etf_issuers',

            [

                'id' => $issuer->id,

                'status_id' => Status::RETIRED,

            ]

        );
    }

    public function test_it_throws_exception_for_invalid_issuer(): void
    {
        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            RetireEtfIssuerService::class
        )->retire(
            999999
        );
    }
}
