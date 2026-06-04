<?php

namespace Tests\Feature\Admin\Securities;

use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityType;
use App\Models\SecurityUpdateSchedule;
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

class ShowSecurityDataTest extends TestCase
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

    public function test_admin_can_view_security_data(): void
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

        $security =
            Security::create([

                'symbol' => 'CHPY',

                'security_type_id' => SecurityType::ETF,

                'status_id' => Status::ACTIVE,

            ]);

        SecurityDetail::create([

            'security_id' => $security->id,

            'security_name' => 'YieldMax Semiconductor Portfolio Option Income ETF',

            'etf_issuer_id' => $issuer->id,

            'etf_strategy_type_id' => 1,

            'distribution_frequency_id' => 1,

            'expense_ratio' => 0.9900,

            'website_url' => 'https://www.yieldmaxetfs.com',

            'notes' => 'Test notes',

        ]);

        SecurityUpdateSchedule::create([

            'security_id' => $security->id,

            'security_update_type_id' => 1,

            'run_day' => 5,

            'run_hour' => 4,

            'status_id' => Status::ACTIVE,

        ]);

        $response =
            $this->getJson(

                '/api/admin/security-data-show/'.
                $security->id

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ])

            ->assertJsonPath(

                'data.id',

                $security->id

            )

            ->assertJsonPath(

                'data.symbol',

                'CHPY'

            )

            ->assertJsonPath(

                'data.detail.security_name',

                'YieldMax Semiconductor Portfolio Option Income ETF'

            )

            ->assertJsonPath(

                'data.detail.website_url',

                'https://www.yieldmaxetfs.com'

            )

            ->assertJsonPath(

                'data.update_schedules.0.run_day',

                5

            )

            ->assertJsonPath(

                'data.update_schedules.0.run_hour',

                4

            );
    }

    public function test_guest_cannot_view_security_data(): void
    {
        $response =
            $this->getJson(

                '/api/admin/security-data-show/1'

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_view_security_data(): void
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

                '/api/admin/security-data-show/1'

            );

        $response

            ->assertUnauthorized()

            ->assertJson([

                'code' => 401,

                'message' => 'Unauthorized',

            ]);
    }

    public function test_it_returns_404_for_missing_security(): void
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

                '/api/admin/security-data-show/999999'

            );

        $response->assertStatus(500);
    }
}
