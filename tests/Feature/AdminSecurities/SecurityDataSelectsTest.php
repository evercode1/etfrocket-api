<?php

namespace Tests\Feature\Admin\Securities;

use App\Models\Status;
use App\Models\User;
use Database\Seeders\DistributionFrequencySeeder;
use Database\Seeders\EtfIssuerSeeder;
use Database\Seeders\EtfStrategyTypeSeeder;
use Database\Seeders\SecurityTypeSeeder;
use Database\Seeders\SecurityUpdateTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityDataSelectsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_update_types')->truncate();

        DB::table('distribution_frequencies')->truncate();

        DB::table('etf_strategy_types')->truncate();

        DB::table('etf_issuers')->truncate();

        DB::table('security_types')->truncate();

        DB::table('statuses')->truncate();

        DB::table('users')->truncate();

        $this->seed([

            StatusSeeder::class,

            SecurityTypeSeeder::class,

            EtfIssuerSeeder::class,

            EtfStrategyTypeSeeder::class,

            DistributionFrequencySeeder::class,

            SecurityUpdateTypeSeeder::class,

        ]);
    }

    protected function tearDown(): void
    {
        DB::table('security_update_types')->truncate();

        DB::table('distribution_frequencies')->truncate();

        DB::table('etf_strategy_types')->truncate();

        DB::table('etf_issuers')->truncate();

        DB::table('security_types')->truncate();

        DB::table('statuses')->truncate();

        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_admin_can_retrieve_security_selects(): void
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

                '/api/admin/security-data-selects'

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ])

            ->assertJsonStructure([

                'success',

                'data' => [

                    'security_types',

                    'statuses',

                    'etf_issuers',

                    'etf_strategy_types',

                    'distribution_frequencies',

                    'security_update_types',

                ],

            ]);
    }

    public function test_guest_cannot_retrieve_security_selects(): void
    {
        $response =
            $this->getJson(

                '/api/admin/security-data-selects'

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_retrieve_security_selects(): void
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

                '/api/admin/security-data-selects'

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

                '/api/admin/security-data-selects'

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
