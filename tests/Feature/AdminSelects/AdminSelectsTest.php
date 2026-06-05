<?php

namespace Tests\Feature\Admin\Selects;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminSelectsTest extends TestCase
{
    public function test_admin_can_view_admin_selects(): void
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

                '/api/admin/admin-selects'

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ])

            ->assertJsonStructure([

                'success',

                'data' => [

                    '*' => [

                        'key',

                        'label',

                        'description',

                        'allow_create',

                        'allow_update',

                        'allow_delete',

                    ],

                ],

            ]);
    }

    public function test_guest_cannot_view_admin_selects(): void
    {
        $response =
            $this->getJson(

                '/api/admin/admin-selects'

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_view_admin_selects(): void
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

                '/api/admin/admin-selects'

            );

        $response

            ->assertUnauthorized()

            ->assertJson([

                'code' => 401,

                'message' => 'Unauthorized',

            ]);
    }

    public function test_it_returns_expected_select_configurations(): void
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

                '/api/admin/admin-selects'

            );

        $response

            ->assertOk()

            ->assertJsonFragment([

                'key' => 'statuses',

                'label' => 'Statuses',

            ])

            ->assertJsonFragment([

                'key' => 'security_types',

                'label' => 'Security Types',

            ])

            ->assertJsonFragment([

                'key' => 'support_topics',

                'label' => 'Support Topics',

            ]);
    }

    public function test_it_does_not_expose_internal_model_details(): void
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

                '/api/admin/admin-selects'

            );

        $response

            ->assertOk()

            ->assertJsonMissingPath(

                'data.0.model'

            )

            ->assertJsonMissingPath(

                'data.0.name_column'

            );
    }

    public function test_results_are_sorted_by_label(): void
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

                '/api/admin/admin-selects'

            );

        $labels =
            collect(

                $response->json(
                    'data'
                )

            )

                ->pluck(
                    'label'
                )

                ->values()

                ->all();

        $sortedLabels =
            collect($labels)

                ->sort()

                ->values()

                ->all();

        $this->assertEquals(

            $sortedLabels,

            $labels

        );
    }
}
