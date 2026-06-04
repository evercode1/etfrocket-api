<?php

namespace Tests\Feature\Admin\Securities;

use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\DistributionFrequencySeeder;
use Database\Seeders\EtfStrategyTypeSeeder;
use Database\Seeders\SecurityTypeSeeder;
use Database\Seeders\SecurityUpdateTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_update_schedules')->truncate();

        DB::table('security_details')->truncate();

        DB::table('securities')->truncate();

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

            EtfStrategyTypeSeeder::class,

            DistributionFrequencySeeder::class,

            SecurityUpdateTypeSeeder::class,

        ]);
    }

    protected function tearDown(): void
    {
        DB::table('security_update_schedules')->truncate();

        DB::table('security_details')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_update_types')->truncate();

        DB::table('distribution_frequencies')->truncate();

        DB::table('etf_strategy_types')->truncate();

        DB::table('etf_issuers')->truncate();

        DB::table('security_types')->truncate();

        DB::table('statuses')->truncate();

        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_admin_can_create_security(): void
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

        $payload = [

            'symbol' => 'CHPY',

            'security_type_id' => 1,

            'status_id' => Status::ACTIVE,

            'security_name' => 'YieldMax Semiconductor Portfolio Option Income ETF',

            'etf_issuer_id' => $issuer->id,

            'etf_strategy_type_id' => 1,

            'distribution_frequency_id' => 1,

            'expense_ratio' => 0.99,

            'website_url' => 'https://www.yieldmaxetfs.com',

            'notes' => 'Feature test security.',

            'schedules' => [

                [

                    'security_update_type_id' => 1,

                    'run_day' => 5,

                    'run_hour' => 4,

                    'status_id' => Status::ACTIVE,

                ],

                [

                    'security_update_type_id' => 2,

                    'run_day' => 1,

                    'run_hour' => 5,

                    'status_id' => Status::ACTIVE,

                ],

            ],

        ];

        $response =
            $this->postJson(

                '/api/admin/security-data-store',

                $payload

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ]);

        $this->assertDatabaseHas(

            'securities',

            [

                'symbol' => 'CHPY',

            ]

        );

        $security =
            Security::where(

                'symbol',

                'CHPY'

            )->firstOrFail();

        $this->assertDatabaseHas(

            'security_details',

            [

                'security_id' => $security->id,

                'security_name' => 'YieldMax Semiconductor Portfolio Option Income ETF',

            ]

        );

        $this->assertDatabaseCount(

            'security_update_schedules',

            2

        );
    }

    public function test_guest_cannot_create_security(): void
    {
        $response =
            $this->postJson(

                '/api/admin/security-data-store',

                []

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_create_security(): void
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

                '/api/admin/security-data-store',

                []

            );

        $response

            ->assertUnauthorized()

            ->assertJson([

                'code' => 401,

                'message' => 'Unauthorized',

            ]);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
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

                '/api/admin/security-data-store',

                []

            );

        $response

            ->assertStatus(422)

            ->assertJsonValidationErrors([

                'symbol',

                'security_type_id',

                'status_id',

                'security_name',

                'schedules',

            ]);
    }
}
