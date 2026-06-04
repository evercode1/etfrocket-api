<?php

namespace Tests\Unit\Services\Admin\Securities;

use App\Models\Security;
use App\Models\SecurityType;
use App\Models\SecurityUpdateSchedule;
use App\Models\Status;
use App\Services\Admin\Securities\RetireSecurityService;
use Database\Seeders\SecurityTypeSeeder;
use Database\Seeders\SecurityUpdateTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RetireSecurityServiceTest extends TestCase
{
    private RetireSecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_update_schedules')->truncate();

        DB::table('security_details')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_update_types')->truncate();

        DB::table('security_types')->truncate();

        DB::table('statuses')->truncate();

        $this->seed([

            StatusSeeder::class,

            SecurityTypeSeeder::class,

            SecurityUpdateTypeSeeder::class,

        ]);

        $this->service =
            app(
                RetireSecurityService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_update_schedules')->truncate();

        DB::table('security_details')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_update_types')->truncate();

        DB::table('security_types')->truncate();

        DB::table('statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_retires_security(): void
    {
        $security =
            Security::create([

                'symbol' => 'CHPY',

                'security_type_id' => SecurityType::ETF,

                'status_id' => Status::ACTIVE,

            ]);

        $this->service->retire(
            $security->id
        );

        $this->assertDatabaseHas(

            'securities',

            [

                'id' => $security->id,

                'status_id' => Status::RETIRED,

            ]

        );
    }

    public function test_it_retires_related_schedules(): void
    {
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

        $this->service->retire(
            $security->id
        );

        $this->assertDatabaseCount(

            'security_update_schedules',

            2

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

    public function test_it_returns_retired_security(): void
    {
        $security =
            Security::create([

                'symbol' => 'CHPY',

                'security_type_id' => SecurityType::ETF,

                'status_id' => Status::ACTIVE,

            ]);

        $retiredSecurity =
            $this->service->retire(
                $security->id
            );

        $this->assertInstanceOf(

            Security::class,

            $retiredSecurity

        );

        $this->assertEquals(

            Status::RETIRED,

            $retiredSecurity->status_id

        );
    }
}
