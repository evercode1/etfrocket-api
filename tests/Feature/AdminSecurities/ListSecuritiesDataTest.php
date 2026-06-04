<?php

namespace Tests\Feature\Admin\Securities;

use App\Models\DistributionFrequency;
use App\Models\EtfIssuer;
use App\Models\EtfStrategyType;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityType;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListSecuritiesDataTest extends TestCase
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

    public function test_admin_can_list_securities_data(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(
            $admin,
            ['*']
        );

        $status =
            Status::create([

                'status_name' => 'Active',

            ]);

        $securityType =
            SecurityType::create([

                'security_type_name' => 'ETF',

            ]);

        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'status_id' => $status->id,

            ]);

        $strategy =
            EtfStrategyType::create([

                'etf_strategy_type_name' => 'Option Income',

            ]);

        $distribution =
            DistributionFrequency::create([

                'distribution_frequency_name' => 'Monthly',

            ]);

        $updateType =
            SecurityUpdateType::create([

                'security_update_type_name' => 'Dividend History',

            ]);

        $security =
            Security::create([

                'symbol' => 'CHPY',

                'security_type_id' => $securityType->id,

                'status_id' => $status->id,

            ]);

        SecurityDetail::create([

            'security_id' => $security->id,

            'security_name' => 'YieldMax Semiconductor Portfolio Option Income ETF',

            'etf_issuer_id' => $issuer->id,

            'etf_strategy_type_id' => $strategy->id,

            'distribution_frequency_id' => $distribution->id,

            'expense_ratio' => 0.9900,

            'website_url' => 'https://www.yieldmaxetfs.com',

            'notes' => 'Test security detail record.',

        ]);

        SecurityUpdateSchedule::create([

            'security_id' => $security->id,

            'security_update_type_id' => $updateType->id,

            'run_day' => 5,

            'run_hour' => 4,

            'status_id' => $status->id,

        ]);

        $response =
            $this->getJson(
                '/api/admin/list-securities-data'
            );

        $response
            ->assertOk()
            ->assertJson([

                'success' => true,

            ])
            ->assertJsonPath(
                'data.data.0.symbol',
                'CHPY'
            )
            ->assertJsonPath(
                'data.data.0.security_name',
                'YieldMax Semiconductor Portfolio Option Income ETF'
            )
            ->assertJsonPath(
                'data.data.0.security_type',
                'ETF'
            )
            ->assertJsonPath(
                'data.data.0.status',
                'Active'
            )
            ->assertJsonPath(
                'data.data.0.issuer',
                'YieldMax'
            )
            ->assertJsonPath(
                'data.data.0.strategy',
                'Option Income'
            )
            ->assertJsonPath(
                'data.data.0.distribution_frequency',
                'Monthly'
            )
            ->assertJsonPath(
                'data.data.0.schedule_count',
                1
            );
    }

    public function test_guest_cannot_list_securities_data(): void
    {
        $response =
            $this->getJson(
                '/api/admin/list-securities-data'
            );

        $response->assertUnauthorized();
    }

    public function test_it_returns_paginated_results()
    {
        $admin =
                User::factory()->create([

                    'is_admin' => 1,

                ]);

        Sanctum::actingAs(
            $admin,
            ['*']
        );

        $status =
            Status::create([

                'status_name' => 'Active',

            ]);

        $securityType =
            SecurityType::create([

                'security_type_name' => 'ETF',

            ]);

        $issuer =
            EtfIssuer::create([

                'etf_issuer_name' => 'YieldMax',

                'status_id' => $status->id,

            ]);

        $strategy =
            EtfStrategyType::create([

                'etf_strategy_type_name' => 'Option Income',

            ]);

        $distribution =
            DistributionFrequency::create([

                'distribution_frequency_name' => 'Monthly',

            ]);

        $updateType =
            SecurityUpdateType::create([

                'security_update_type_name' => 'Dividend History',

            ]);

        $security =
            Security::create([

                'symbol' => 'CHPY',

                'security_type_id' => $securityType->id,

                'status_id' => $status->id,

            ]);

        SecurityDetail::create([

            'security_id' => $security->id,

            'security_name' => 'YieldMax Semiconductor Portfolio Option Income ETF',

            'etf_issuer_id' => $issuer->id,

            'etf_strategy_type_id' => $strategy->id,

            'distribution_frequency_id' => $distribution->id,

            'expense_ratio' => 0.9900,

            'website_url' => 'https://www.yieldmaxetfs.com',

            'notes' => 'Test security detail record.',

        ]);

        SecurityUpdateSchedule::create([

            'security_id' => $security->id,

            'security_update_type_id' => $updateType->id,

            'run_day' => 5,

            'run_hour' => 4,

            'status_id' => $status->id,

        ]);

        $response =
            $this->getJson(
                '/api/admin/list-securities-data'
            );

        $response

            ->assertOk()

            ->assertJsonStructure([

                'success',

                'data' => [

                    'current_page',

                    'data',

                    'per_page',

                    'total',

                ],

            ]);
    }

    public function test_non_admin_cannot_list_securities_data(): void
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
                '/api/admin/list-securities-data'
            );

        $response
            ->assertUnauthorized()
            ->assertJson([
                'code' => 401,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_it_honors_per_page_parameter(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(
            $admin,
            ['*']
        );

        $status =
            Status::create([
                'status_name' => 'Active',
            ]);

        $securityType =
            SecurityType::create([
                'security_type_name' => 'ETF',
            ]);

        for ($i = 1; $i <= 30; $i++) {

            Security::create([

                'symbol' => 'ETF'.$i,

                'security_type_id' => $securityType->id,

                'status_id' => $status->id,

            ]);
        }

        $response =
            $this->getJson(

                '/api/admin/list-securities-data?per_page=10'

            );

        $response

            ->assertOk()

            ->assertJsonPath(

                'data.per_page',

                10

            )

            ->assertJsonCount(

                10,

                'data.data'

            );
    }

    public function test_it_can_search_by_symbol(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(
            $admin,
            ['*']
        );

        $status =
            Status::create([
                'status_name' => 'Active',
            ]);

        $securityType =
            SecurityType::create([
                'security_type_name' => 'ETF',
            ]);

        Security::create([

            'symbol' => 'CHPY',

            'security_type_id' => $securityType->id,

            'status_id' => $status->id,

        ]);

        Security::create([

            'symbol' => 'NVII',

            'security_type_id' => $securityType->id,

            'status_id' => $status->id,

        ]);

        $response =
            $this->getJson(

                '/api/admin/list-securities-data?search=CHPY'

            );

        $response

            ->assertOk()

            ->assertJsonCount(

                1,

                'data.data'

            )

            ->assertJsonPath(

                'data.data.0.symbol',

                'CHPY'

            );
    }
}
