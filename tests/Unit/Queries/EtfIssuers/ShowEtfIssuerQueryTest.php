<?php

namespace Tests\Unit\Queries\Admin\EtfIssuers;

use App\Models\EtfIssuer;
use App\Models\Status;
use App\Queries\Admin\EtfIssuers\ShowEtfIssuerQuery;
use Database\Seeders\StatusSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShowEtfIssuerQueryTest extends TestCase
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

    public function test_it_returns_issuer_with_relationships(): void
    {
        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'website_url' => 'https://yieldmaxetfs.com',

                'status_id' => Status::ACTIVE,

                'notes' => 'Covered call ETF issuer.',

            ]);

        $result =
            app(
                ShowEtfIssuerQuery::class
            )->getData(
                $issuer->id
            );

        $this->assertInstanceOf(
            EtfIssuer::class,
            $result
        );

        $this->assertEquals(
            $issuer->id,
            $result->id
        );

        $this->assertEquals(
            'YieldMax',
            $result->etf_issuer_name
        );

        $this->assertEquals(
            'https://yieldmaxetfs.com',
            $result->website_url
        );

        $this->assertEquals(
            Status::ACTIVE,
            $result->status_id
        );

        $this->assertEquals(
            'Covered call ETF issuer.',
            $result->notes
        );

        $this->assertNotNull(
            $result->status
        );

        $this->assertEquals(
            'Active',
            $result
                ->status
                ->status_name
        );
    }

    public function test_it_throws_exception_for_invalid_issuer(): void
    {
        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            ShowEtfIssuerQuery::class
        )->getData(
            999999
        );
    }
}
