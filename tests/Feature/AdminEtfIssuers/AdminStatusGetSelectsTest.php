<?php

namespace Tests\Feature\Admin\Statuses;

use App\Models\Status;
use App\Models\User;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminStatusGetSelectsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('statuses')->truncate();

        DB::table('users')->truncate();

        $this->seed(
            StatusSeeder::class
        );
    }

    protected function tearDown(): void
    {
        DB::table('statuses')->truncate();

        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_admin_can_retrieve_status_selects(): void
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

                '/api/admin/etf-issuer-selects'

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ])

            ->assertJsonStructure([

                'success',

                'data',

            ]);
    }

    public function test_guest_cannot_retrieve_status_selects(): void
    {
        $response =
            $this->getJson(

                '/api/admin/etf-issuer-selects'

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_retrieve_status_selects(): void
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

                '/api/admin/etf-issuer-selects'

            );

        $response

            ->assertUnauthorized()

            ->assertJson([

                'code' => 401,

                'message' => 'Unauthorized',

            ]);
    }

    public function test_it_returns_seeded_statuses(): void
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

                '/api/admin/etf-issuer-selects'

            );

        $response

            ->assertOk()

            ->assertJsonFragment([

                Status::ACTIVE => 'Active',

            ])

            ->assertJsonFragment([

                Status::RETIRED => 'Retired',

            ])

            ->assertJsonFragment([

                Status::PENDING => 'Pending',

            ]);
    }
}
