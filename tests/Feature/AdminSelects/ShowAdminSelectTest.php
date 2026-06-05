<?php

namespace Tests\Feature\Admin\Selects;

use App\Models\User;
use Database\Seeders\DistributionFrequencySeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowAdminSelectTest extends TestCase
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

    public function test_admin_can_view_select_configuration(): void
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

                '/api/admin/admin-selects/distribution_frequencies'

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ])

            ->assertJsonPath(

                'config.key',

                'distribution_frequencies'

            )

            ->assertJsonPath(

                'config.label',

                'Distribution Frequencies'

            )

            ->assertJsonPath(

                'config.allow_create',

                true

            )

            ->assertJsonPath(

                'config.allow_update',

                true

            )

            ->assertJsonPath(

                'config.allow_delete',

                false

            );
    }

    public function test_admin_can_view_select_rows(): void
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

                '/api/admin/admin-selects/distribution_frequencies'

            );

        $response

            ->assertOk()

            ->assertJsonStructure([

                'success',

                'config' => [

                    'key',

                    'label',

                    'description',

                    'allow_create',

                    'allow_update',

                    'allow_delete',

                ],

                'rows' => [

                    '*' => [

                        'id',

                        'name',

                    ],

                ],

            ]);
    }

    public function test_guest_cannot_view_select_configuration(): void
    {
        $response =
            $this->getJson(

                '/api/admin/admin-selects/distribution_frequencies'

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_view_select_configuration(): void
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

                '/api/admin/admin-selects/distribution_frequencies'

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
            $this->getJson(

                '/api/admin/admin-selects/not_a_real_select'

            );

        $response

            ->assertStatus(404)

            ->assertJson([

                'success' => false,

                'message' => 'Select configuration not found.',

            ]);
    }

    public function test_rows_are_sorted_by_name(): void
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

                '/api/admin/admin-selects/distribution_frequencies'

            );

        $names =
            collect(

                $response->json(
                    'rows'
                )

            )

                ->pluck(
                    'name'
                )

                ->values()

                ->all();

        $sortedNames =
            collect($names)

                ->sort()

                ->values()

                ->all();

        $this->assertEquals(

            $sortedNames,

            $names

        );
    }
}
