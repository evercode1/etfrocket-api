<?php

namespace Tests\Feature\Admin\Selects;

use App\Models\User;
use Database\Seeders\DistributionFrequencySeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreAdminSelectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('distribution_frequencies')->truncate();

        $this->seed([

            DistributionFrequencySeeder::class,

        ]);
    }

    protected function tearDown(): void
    {
        DB::table('distribution_frequencies')->truncate();

        parent::tearDown();
    }

    public function test_admin_can_create_select_value(): void
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

                '/api/admin/admin-selects/distribution_frequencies',

                [

                    'name' => 'Semi-Monthly',

                ]

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ])

            ->assertJsonPath(

                'data.name',

                'Semi-Monthly'

            );

        $this->assertDatabaseHas(

            'distribution_frequencies',

            [

                'distribution_frequency_name' => 'Semi-Monthly',

            ]

        );
    }

    public function test_guest_cannot_create_select_value(): void
    {
        $response =
            $this->postJson(

                '/api/admin/admin-selects/distribution_frequencies',

                [

                    'name' => 'Semi-Monthly',

                ]

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_create_select_value(): void
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

                '/api/admin/admin-selects/distribution_frequencies',

                [

                    'name' => 'Semi-Monthly',

                ]

            );

        $response

            ->assertUnauthorized()

            ->assertJson([

                'code' => 401,

                'message' => 'Unauthorized',

            ]);
    }

    public function test_invalid_key_returns_not_found(): void
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

                '/api/admin/admin-selects/not_a_real_select',

                [

                    'name' => 'Test',

                ]

            );

        $response

            ->assertStatus(404)

            ->assertJson([

                'success' => false,

                'message' => 'Select configuration not found.',

            ]);
    }

    public function test_name_is_required(): void
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

                '/api/admin/admin-selects/distribution_frequencies',

                []

            );

        $response

            ->assertStatus(422)

            ->assertJsonValidationErrors([

                'name',

            ]);
    }

    public function test_creation_is_blocked_when_allow_create_is_false(): void
    {
        config()->set(

            'admin_selects.distribution_frequencies.allow_create',

            false

        );

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

                '/api/admin/admin-selects/distribution_frequencies',

                [

                    'name' => 'Semi-Monthly',

                ]

            );

        $response

            ->assertStatus(403)

            ->assertJson([

                'success' => false,

                'message' => 'Creation is not allowed for this select.',

            ]);
    }
}
