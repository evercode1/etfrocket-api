<?php

namespace Tests\Feature\Admin\Selects;

use App\Models\DistributionFrequency;
use App\Models\User;
use Database\Seeders\DistributionFrequencySeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateAdminSelectTest extends TestCase
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

    public function test_admin_can_update_select_value(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(

            $admin,

            ['*']

        );

        $record =
            DistributionFrequency::query()

                ->first();

        $response =
            $this->putJson(

                "/api/admin/admin-selects/distribution_frequencies/{$record->id}",

                [

                    'name' => 'Updated Frequency',

                ]

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ])

            ->assertJsonPath(

                'data.id',

                $record->id

            )

            ->assertJsonPath(

                'data.name',

                'Updated Frequency'

            );

        $this->assertDatabaseHas(

            'distribution_frequencies',

            [

                'id' => $record->id,

                'distribution_frequency_name' => 'Updated Frequency',

            ]

        );
    }

    public function test_guest_cannot_update_select_value(): void
    {
        $record =
            DistributionFrequency::query()

                ->first();

        $response =
            $this->putJson(

                "/api/admin/admin-selects/distribution_frequencies/{$record->id}",

                [

                    'name' => 'Updated Frequency',

                ]

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_update_select_value(): void
    {
        $user =
            User::factory()->create([

                'is_admin' => 0,

            ]);

        Sanctum::actingAs(

            $user,

            ['*']

        );

        $record =
            DistributionFrequency::query()

                ->first();

        $response =
            $this->putJson(

                "/api/admin/admin-selects/distribution_frequencies/{$record->id}",

                [

                    'name' => 'Updated Frequency',

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
            $this->putJson(

                '/api/admin/admin-selects/not_a_real_select/1',

                [

                    'name' => 'Updated Frequency',

                ]

            );

        $response

            ->assertStatus(404)

            ->assertJson([

                'success' => false,

                'message' => 'Select configuration not found.',

            ]);
    }

    public function test_invalid_id_returns_not_found(): void
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
            $this->putJson(

                '/api/admin/admin-selects/distribution_frequencies/999999',

                [

                    'name' => 'Updated Frequency',

                ]

            );

        $response

            ->assertStatus(404)

            ->assertJson([

                'success' => false,

                'message' => 'Select value not found.',

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

        $record =
            DistributionFrequency::query()

                ->first();

        $response =
            $this->putJson(

                "/api/admin/admin-selects/distribution_frequencies/{$record->id}",

                []

            );

        $response

            ->assertStatus(422)

            ->assertJsonValidationErrors([

                'name',

            ]);
    }

    public function test_update_is_blocked_when_allow_update_is_false(): void
    {
        config()->set(

            'admin_selects.distribution_frequencies.allow_update',

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

        $record =
            DistributionFrequency::query()

                ->first();

        $response =
            $this->putJson(

                "/api/admin/admin-selects/distribution_frequencies/{$record->id}",

                [

                    'name' => 'Updated Frequency',

                ]

            );

        $response

            ->assertStatus(403)

            ->assertJson([

                'success' => false,

                'message' => 'Updating is not allowed for this select.',

            ]);
    }
}
