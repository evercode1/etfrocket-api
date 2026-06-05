<?php

namespace Tests\Feature\Admin\EtfIssuers;

use App\Models\EtfIssuer;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCreateEtfIssuerTest extends TestCase
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

    public function test_admin_can_create_etf_issuer(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

        $payload = [

            'etf_issuer_name' => 'YieldMax',

            'website_url' => 'https://yieldmaxetfs.com',

            'status_id' => Status::ACTIVE,

            'notes' => 'Covered call ETF issuer.',

        ];

        $response =
            $this->postJson(

                '/api/admin/etf-issuer-store',

                $payload

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ])

            ->assertJsonPath(

                'data.etf_issuer_name',

                'YieldMax'

            )

            ->assertJsonPath(

                'data.website_url',

                'https://yieldmaxetfs.com'

            )

            ->assertJsonPath(

                'data.status_id',

                Status::ACTIVE

            )

            ->assertJsonPath(

                'data.notes',

                'Covered call ETF issuer.'

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
    }

    public function test_guest_cannot_create_etf_issuer(): void
    {
        $response =
            $this->postJson(

                '/api/admin/etf-issuer-store',

                []

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_create_etf_issuer(): void
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
            $this->postJson(

                '/api/admin/etf-issuer-store',

                [

                    'etf_issuer_name' => 'YieldMax',

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

        $response =
            $this->postJson(

                '/api/admin/etf-issuer-store',

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

        $response =
            $this->postJson(

                '/api/admin/etf-issuer-store',

                [

                    'etf_issuer_name' => 'YieldMax',

                ]

            );

        $response

            ->assertStatus(422)

            ->assertJsonValidationErrors([

                'status_id',

            ]);
    }

    public function test_it_prevents_duplicate_issuer_names(): void
    {
        EtfIssuer::create([

            'etf_issuer_name' => 'YieldMax',

            'status_id' => Status::ACTIVE,

        ]);

        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

        $response =
            $this->postJson(

                '/api/admin/etf-issuer-store',

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
