<?php

namespace Tests\Unit\Services\Admin\EtfIssuers;

use App\Models\EtfIssuer;
use App\Models\Status;
use App\Services\Admin\EtfIssuers\UpdateEtfIssuerService;
use Database\Seeders\StatusSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpdateEtfIssuerServiceTest extends TestCase
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

    public function test_it_updates_an_etf_issuer(): void
    {
        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'website_url' => 'https://yieldmaxetfs.com',

                'status_id' => Status::ACTIVE,

                'notes' => 'Original notes.',

            ]);

        $updatedIssuer =
            app(
                UpdateEtfIssuerService::class
            )->update(

                $issuer->id,

                [

                    'etf_issuer_name' => 'YieldMax Updated',

                    'website_url' => 'https://updated-yieldmax.com',

                    'status_id' => Status::RETIRED,

                    'notes' => 'Updated notes.',

                ]

            );

        $this->assertInstanceOf(
            EtfIssuer::class,
            $updatedIssuer
        );

        $this->assertEquals(
            $issuer->id,
            $updatedIssuer->id
        );

        $this->assertEquals(
            'YieldMax Updated',
            $updatedIssuer->etf_issuer_name
        );

        $this->assertEquals(
            'https://updated-yieldmax.com',
            $updatedIssuer->website_url
        );

        $this->assertEquals(
            Status::RETIRED,
            $updatedIssuer->status_id
        );

        $this->assertEquals(
            'Updated notes.',
            $updatedIssuer->notes
        );

        $this->assertDatabaseHas(

            'etf_issuers',

            [

                'id' => $issuer->id,

                'etf_issuer_name' => 'YieldMax Updated',

                'website_url' => 'https://updated-yieldmax.com',

                'status_id' => Status::RETIRED,

                'notes' => 'Updated notes.',

            ]

        );
    }

    public function test_it_can_clear_optional_fields(): void
    {
        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'website_url' => 'https://yieldmaxetfs.com',

                'status_id' => Status::ACTIVE,

                'notes' => 'Original notes.',

            ]);

        $updatedIssuer =
            app(
                UpdateEtfIssuerService::class
            )->update(

                $issuer->id,

                [

                    'etf_issuer_name' => 'YieldMax',

                    'website_url' => null,

                    'status_id' => Status::ACTIVE,

                    'notes' => null,

                ]

            );

        $this->assertNull(
            $updatedIssuer->website_url
        );

        $this->assertNull(
            $updatedIssuer->notes
        );

        $this->assertDatabaseHas(

            'etf_issuers',

            [

                'id' => $issuer->id,

                'website_url' => null,

                'notes' => null,

            ]

        );
    }

    public function test_it_throws_exception_for_invalid_issuer(): void
    {
        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            UpdateEtfIssuerService::class
        )->update(

            999999,

            [

                'etf_issuer_name' => 'Invalid',

                'status_id' => Status::ACTIVE,

            ]

        );
    }
}
