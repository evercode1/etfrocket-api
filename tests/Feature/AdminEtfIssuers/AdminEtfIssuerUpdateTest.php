<?php

namespace Tests\Feature\Admin\EtfIssuers;

use App\Models\EtfIssuer;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminEtfIssuerUpdateTest extends TestCase
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

    public function test_admin_can_update_etf_issuer(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'website_url' => 'https://yieldmaxetfs.com',

                'status_id' => Status::ACTIVE,

                'notes' => 'Original notes.',

            ]);

        $payload = [

            'etf_issuer_name' => 'YieldMax Updated',

            'website_url' => 'https://updated-yieldmax.com',

            'status_id' => Status::RETIRED,

            'notes' => 'Updated notes.',

        ];

        $response =
            $this->putJson(

                '/api/admin/etf-issuer-update/'.

                $issuer->id,

                $payload

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ])

            ->assertJsonPath(

                'data.id',

                $issuer->id

            )

            ->assertJsonPath(

                'data.etf_issuer_name',

                'YieldMax Updated'

            )

            ->assertJsonPath(

                'data.website_url',

                'https://updated-yieldmax.com'

            )

            ->assertJsonPath(

                'data.status_id',

                Status::RETIRED

            )

            ->assertJsonPath(

                'data.notes',

                'Updated notes.'

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

    public function test_guest_cannot_update_etf_issuer(): void
    {
        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'status_id' => Status::ACTIVE,

            ]);

        $response =
            $this->putJson(

                '/api/admin/etf-issuer-update/'.

                $issuer->id,

                []

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_update_etf_issuer(): void
    {
        $user =
            User::factory()->create([

                'is_admin' => 0,

            ]);

        Sanctum::actingAs(

            $user,

            ['*']

        );

        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'status_id' => Status::ACTIVE,

            ]);

        $response =
            $this->putJson(

                '/api/admin/etf-issuer-update/'.

                $issuer->id,

                [

                    'etf_issuer_name' => 'Updated',

                    'status_id' => Status::ACTIVE,

                ]

            );

        $response

            ->assertUnauthorized()

            ->assertJson([

                'code' => 401,

                'message' => 'Unauthorized',

            ]);
    }

    public function test_it_requires_etf_issuer_name(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'status_id' => Status::ACTIVE,

            ]);

        $response =
            $this->putJson(

                '/api/admin/etf-issuer-update/'.

                $issuer->id,

                [

                    'status_id' => Status::ACTIVE,

                ]

            );

        $response

            ->assertStatus(422)

            ->assertJsonValidationErrors([

                'etf_issuer_name',

            ]);
    }

    public function test_it_requires_status_id(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'status_id' => Status::ACTIVE,

            ]);

        $response =
            $this->putJson(

                '/api/admin/etf-issuer-update/'.

                $issuer->id,

                [

                    'etf_issuer_name' => 'YieldMax Updated',

                ]

            );

        $response

            ->assertStatus(422)

            ->assertJsonValidationErrors([

                'status_id',

            ]);
    }

    public function test_it_allows_existing_name_on_same_record(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'status_id' => Status::ACTIVE,

            ]);

        $response =
            $this->putJson(

                '/api/admin/etf-issuer-update/'.

                $issuer->id,

                [

                    'etf_issuer_name' => 'YieldMax',

                    'status_id' => Status::ACTIVE,

                ]

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ]);
    }

    public function test_it_prevents_duplicate_issuer_names(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

        $issuerOne =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'status_id' => Status::ACTIVE,

            ]);

        $issuerTwo =
            EtfIssuer::create([

                'etf_issuer_name' => 'Roundhill',

                'status_id' => Status::ACTIVE,

            ]);

        $response =
            $this->putJson(

                '/api/admin/etf-issuer-update/'.

                $issuerTwo->id,

                [

                    'etf_issuer_name' => 'YieldMax',

                    'status_id' => Status::ACTIVE,

                ]

            );

        $response

            ->assertStatus(422)

            ->assertJsonValidationErrors([

                'etf_issuer_name',

            ]);
    }
}
