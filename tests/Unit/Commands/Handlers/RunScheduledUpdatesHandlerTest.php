<?php

namespace Tests\Unit\Crons\Handlers;

use App\Jobs\RunScheduledSecurityUpdateJob;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityUpdateSchedule;
use App\Models\Status;
use App\Services\Crons\Handlers\RunScheduledUpdatesHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunScheduledUpdatesHandlerTest extends TestCase
{
    private RunScheduledUpdatesHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_ingestion_batch_items')
            ->truncate();

        DB::table('security_ingestion_batches')
            ->truncate();

        DB::table('security_update_schedules')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        $this->handler =
            app(
                RunScheduledUpdatesHandler::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_ingestion_batch_items')
            ->truncate();

        DB::table('security_ingestion_batches')
            ->truncate();

        DB::table('security_update_schedules')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_creates_batch_batch_items_and_dispatches_jobs()
    {
        Queue::fake();

        SecurityUpdateSchedule::factory()

            ->dividend()

            ->scheduledFor(
                1,
                2
            )

            ->create([
                'status_id' => Status::ACTIVE,
            ]);

        SecurityUpdateSchedule::factory()

            ->nav()

            ->scheduledFor(
                1,
                2
            )

            ->create([
                'status_id' => Status::ACTIVE,
            ]);

        SecurityUpdateSchedule::factory()

            ->aum()

            ->scheduledFor(
                5,
                8
            )

            ->create([
                'status_id' => Status::ACTIVE,
            ]);

        $result =

            $this->handler
                ->handleRunScheduledUpdates([

                    'day' => 1,

                    'hour' => 2,

                ]);

        $this->assertEquals(
            1,
            $result['success']
        );

        $this->assertNull(
            $result['cron_fail_details']
        );

        $this->assertDatabaseCount(
            'security_ingestion_batches',
            1
        );

        $this->assertDatabaseCount(
            'security_ingestion_batch_items',
            2
        );

        $batch =

            SecurityIngestionBatch::firstOrFail();

        $this->assertEquals(
            2,
            $batch->total_securities
        );

        Queue::assertPushed(
            RunScheduledSecurityUpdateJob::class,
            2
        );
    }

    public function test_it_returns_success_when_no_schedules_exist()
    {
        Queue::fake();

        $result =

            $this->handler
                ->handleRunScheduledUpdates([

                    'day' => 1,

                    'hour' => 2,

                ]);

        $this->assertEquals(
            1,
            $result['success']
        );

        $this->assertNull(
            $result['cron_fail_details']
        );

        $this->assertDatabaseCount(
            'security_ingestion_batches',
            0
        );

        $this->assertDatabaseCount(
            'security_ingestion_batch_items',
            0
        );

        Queue::assertNothingPushed();
    }

    public function test_it_ignores_inactive_schedules()
    {
        Queue::fake();

        SecurityUpdateSchedule::factory()

            ->dividend()

            ->scheduledFor(
                1,
                2
            )

            ->create([
                'status_id' => Status::INACTIVE,
            ]);

        $result =

            $this->handler
                ->handleRunScheduledUpdates([

                    'day' => 1,

                    'hour' => 2,

                ]);

        $this->assertEquals(
            1,
            $result['success']
        );

        $this->assertDatabaseCount(
            'security_ingestion_batches',
            0
        );

        $this->assertDatabaseCount(
            'security_ingestion_batch_items',
            0
        );

        Queue::assertNothingPushed();
    }

    public function test_it_honors_run_day_and_run_hour()
    {
        Queue::fake();

        SecurityUpdateSchedule::factory()

            ->dividend()

            ->scheduledFor(
                1,
                2
            )

            ->create();

        SecurityUpdateSchedule::factory()

            ->nav()

            ->scheduledFor(
                1,
                3
            )

            ->create();

        SecurityUpdateSchedule::factory()

            ->aum()

            ->scheduledFor(
                2,
                2
            )

            ->create();

        $this->handler
            ->handleRunScheduledUpdates([

                'day' => 1,

                'hour' => 2,

            ]);

        $this->assertDatabaseCount(
            'security_ingestion_batches',
            1
        );

        $this->assertDatabaseCount(
            'security_ingestion_batch_items',
            1
        );

        Queue::assertPushed(
            RunScheduledSecurityUpdateJob::class,
            1
        );
    }
}
