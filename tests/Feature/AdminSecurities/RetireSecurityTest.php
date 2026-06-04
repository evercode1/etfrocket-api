<?php

namespace Tests\Feature\Admin\Securities;

use App\Models\Security;
use App\Models\SecurityType;
use App\Models\SecurityUpdateSchedule;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\SecurityTypeSeeder;
use Database\Seeders\SecurityUpdateTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RetireSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_update_schedules')->truncate();

        DB::table('security_details')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_update_types')->truncate();

        DB::table('security_types')->truncate();

        DB::table('statuses')->truncate();

        DB::table('users')->truncate();

        $this->seed([

            StatusSeeder::class,

            SecurityTypeSeeder::class,

            SecurityUpdateTypeSeeder::class,

        ]);
    }

    protected function tearDown(): void
    {
        DB::table('security_update_schedules')->truncate();

        DB::table('security_details')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_update_types')->truncate();

        DB::table('security_types')->truncate();

        DB::table('statuses')->truncate();

        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_admin_can_retire_security(): void
    {
        $admin =
            User::factory()->create([

                'is_admin' => 1,

            ]);

        Sanctum::actingAs(
            $admin,
            ['*']
        );

        $security =
            Security::create([

                'symbol' => 'CHPY',

                'security_type_id' => SecurityType::ETF,

                'status_id' => Status::ACTIVE,

            ]);

        SecurityUpdateSchedule::create([

            'security_id' => $security->id,

            'security_update_type_id' => 1,

            'run_day' => 5,

            'run_hour' => 4,

            'status_id' => Status::ACTIVE,

        ]);

        SecurityUpdateSchedule::create([

            'security_id' => $security->id,

            'security_update_type_id' => 2,

            'run_day' => 1,

            'run_hour' => 5,

            'status_id' => Status::ACTIVE,

        ]);

        $response =
            $this->putJson(

                '/api/admin/security-data-retire/'.
                $security->id

            );

        $response

            ->assertOk()

            ->assertJson([

                'success' => true,

            ]);

        $this->assertDatabaseHas(

            'securities',

            [

                'id' => $security->id,

                'status_id' => Status::RETIRED,

            ]

        );

        $this->assertDatabaseHas(

            'security_update_schedules',

            [

                'security_id' => $security->id,

                'security_update_type_id' => 1,

                'status_id' => Status::RETIRED,

            ]

        );

        $this->assertDatabaseHas(

            'security_update_schedules',

            [

                'security_id' => $security->id,

                'security_update_type_id' => 2,

                'status_id' => Status::RETIRED,

            ]

        );
    }

    public function test_guest_cannot_retire_security(): void
    {
        $response =
            $this->putJson(

                '/api/admin/security-data-retire/1'

            );

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_retire_security(): void
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
            $this->putJson(

                '/api/admin/security-data-retire/1'

            );

        $response

            ->assertUnauthorized()

            ->assertJson([

                'code' => 401,

                'message' => 'Unauthorized',

            ]);
    }
}
