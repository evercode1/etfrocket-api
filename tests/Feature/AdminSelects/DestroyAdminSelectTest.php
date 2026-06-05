<?php

namespace Tests\Feature\Admin\Selects;

use App\Models\DistributionFrequency;
use App\Models\User;
use Database\Seeders\DistributionFrequencySeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DestroyAdminSelectTest extends TestCase
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

    public function test_admin_can_delete_select_value(): void
    {
        config()->set(

            'admin_selects.distribution_frequencies.allow_delete',

            true

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
            $this->deleteJson(

                "/api/admin/admin-selects/distribution_frequencies/{$record->id}"

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ]);

        $this->assertDatabaseMissing(

            'distribution_frequencies',

            [

                'id' => $record->id,

            ]

        );
    }

    public function test_guest_cannot_delete_select_value(): void
    {
        config()->set(

            'admin_selects.distribution_frequencies.allow_delete',

            true

        );

        $record =
            DistributionFrequency::query()

                ->first();

        $response =
            $this->deleteJson(

                "/api/admin/admin-selects/distribution_frequencies/{$record->id}"

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_delete_select_value(): void
    {
        config()->set(

            'admin_selects.distribution_frequencies.allow_delete',

            true

        );

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
            $this->deleteJson(

                "/api/admin/admin-selects/distribution_frequencies/{$record->id}"

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
            $this->deleteJson(

                '/api/admin/admin-selects/not_a_real_select/1'

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
        config()->set(

            'admin_selects.distribution_frequencies.allow_delete',

            true

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
            $this->deleteJson(

                '/api/admin/admin-selects/distribution_frequencies/999999'

            );

        $response

            ->assertStatus(404)

            ->assertJson([

                'success' => false,

                'message' => 'Select value not found.',

            ]);
    }

    public function test_delete_is_blocked_when_allow_delete_is_false(): void
    {
        config()->set(

            'admin_selects.distribution_frequencies.allow_delete',

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
            $this->deleteJson(

                "/api/admin/admin-selects/distribution_frequencies/{$record->id}"

            );

        $response

            ->assertStatus(403)

            ->assertJson([

                'success' => false,

                'message' => 'Deleting is not allowed for this select.',

            ]);
    }
}
