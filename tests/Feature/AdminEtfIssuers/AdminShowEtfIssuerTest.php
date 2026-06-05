<?php

namespace Tests\Feature\Admin\EtfIssuers;

use App\Models\EtfIssuer;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminShowEtfIssuerTest extends TestCase
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

    public function test_admin_can_view_etf_issuer(): void
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

                'notes' => 'Covered call ETF issuer.',

            ]);

        $response =
            $this->getJson(

                '/api/admin/etf-issuer-show/'.

                $issuer->id

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

            )

            ->assertJsonPath(

                'data.status.status_name',

                'Active'

            );
    }

    public function test_guest_cannot_view_etf_issuer(): void
    {
        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'status_id' => Status::ACTIVE,

            ]);

        $response =
            $this->getJson(

                '/api/admin/etf-issuer-show/'.

                $issuer->id

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_view_etf_issuer(): void
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
            $this->getJson(

                '/api/admin/etf-issuer-show/'.

                $issuer->id

            );

        $response

            ->assertUnauthorized()

            ->assertJson([

                'code' => 401,

                'message' => 'Unauthorized',

            ]);
    }

    public function test_it_returns_not_found_for_invalid_issuer(): void
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
            $this->getJson(

                '/api/admin/etf-issuer-show/999999'

            );

        $response

            ->assertStatus(500)

            ->assertJson([

                'success' => false,

                'message' => 'ETF issuer not found.',

            ]);
    }
}
