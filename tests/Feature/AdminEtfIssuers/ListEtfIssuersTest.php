<?php

namespace Tests\Feature\Admin\EtfIssuers;

use App\Models\EtfIssuer;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListEtfIssuersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_issuers')->truncate();

        DB::table('statuses')->truncate();

        DB::table('users')->truncate();

        $this->seed(
            StatusSeeder::class
        );
    }

    protected function tearDown(): void
    {
        DB::table('etf_issuers')->truncate();

        DB::table('statuses')->truncate();

        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_admin_can_list_etf_issuers(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

        EtfIssuer::create([

            'etf_issuer_name' => 'YieldMax',

            'website_url' => 'https://yieldmaxetfs.com',

            'status_id' => Status::ACTIVE,

            'notes' => 'Covered call ETF issuer.',

        ]);

        $response =
            $this->getJson(

                '/api/admin/list-etf-issuers'

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ])

            ->assertJsonPath(

                'data.data.0.etf_issuer_name',

                'YieldMax'

            )

            ->assertJsonPath(

                'data.data.0.website_url',

                'https://yieldmaxetfs.com'

            )

            ->assertJsonPath(

                'data.data.0.status',

                'Active'

            )

            ->assertJsonPath(

                'meta.total_active',

                1

            )

            ->assertJsonPath(

                'meta.total_retired',

                0

            );
    }

    public function test_guest_cannot_list_etf_issuers(): void
    {
        $response =
            $this->getJson(

                '/api/admin/list-etf-issuers'

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_list_etf_issuers(): void
    {
        $user =
            User::factory()->create([

                'is_admin' => 0,

            ]);

        Sanctum::actingAs(

            $user,

            ['*']

        );

        $response =
            $this->getJson(

                '/api/admin/list-etf-issuers'

            );

        $response

            ->assertUnauthorized()

            ->assertJson([

                'code' => 401,

                'message' => 'Unauthorized',

            ]);
    }

    public function test_it_returns_paginated_results(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

        EtfIssuer::create([

            'etf_issuer_name' => 'YieldMax',

            'website_url' => 'https://yieldmaxetfs.com',

            'status_id' => Status::ACTIVE,

        ]);

        $response =
            $this->getJson(

                '/api/admin/list-etf-issuers'

            );

        $response

            ->assertOk()

            ->assertJsonStructure([

                'success',

                'data' => [

                    'current_page',

                    'data',

                    'per_page',

                    'total',

                ],

                'meta' => [

                    'total_active',

                    'total_retired',

                ],

            ]);
    }

    public function test_it_honors_per_page_parameter(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

        for ($i = 1; $i <= 30; $i++) {

            EtfIssuer::create([

                'etf_issuer_name' => 'Issuer '.$i,

                'website_url' => 'https://issuer'.$i.'.example.com',

                'status_id' => Status::ACTIVE,

            ]);
        }

        $response =
            $this->getJson(

                '/api/admin/list-etf-issuers?per_page=10'

            );

        $response

            ->assertOk()

            ->assertJsonPath(

                'data.per_page',

                10

            )

            ->assertJsonCount(

                10,

                'data.data'

            );
    }

    public function test_it_can_search_by_issuer_name(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

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

        $response =
            $this->getJson(

                '/api/admin/list-etf-issuers?search=YieldMax'

            );

        $response

            ->assertOk()

            ->assertJsonCount(

                1,

                'data.data'

            )

            ->assertJsonPath(

                'data.data.0.etf_issuer_name',

                'YieldMax'

            );
    }

    public function test_it_can_filter_by_status(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

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

        $response =
            $this->getJson(

                '/api/admin/list-etf-issuers?status_id='.

                Status::RETIRED

            );

        $response

            ->assertOk()

            ->assertJsonCount(

                1,

                'data.data'

            )

            ->assertJsonPath(

                'data.data.0.etf_issuer_name',

                'Retired Issuer'

            );
    }
}
