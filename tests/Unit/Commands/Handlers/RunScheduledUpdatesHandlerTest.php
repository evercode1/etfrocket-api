<?php

namespace Tests\Unit\Crons\Handlers;

use App\Jobs\RunScheduledSecurityUpdateJob;
use App\Models\EtfIssuer;
use App\Models\SecurityDetail;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
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

    public function test_it_creates_batch_batch_items_and_dispatches_jobs_for_fund_data()
    {
        Queue::fake();

        SecurityUpdateSchedule::factory()

            ->aum()

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
                3,
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

        $this->assignSupportedIssuer();

        $result =

            $this->handler
                ->handleRunScheduledUpdates([

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

    public function test_it_skips_dividend_schedules()
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

            ->aum()

            ->scheduledFor(
                1,
                2
            )

            ->create([
                'status_id' => Status::ACTIVE,
            ]);

        $this->assignSupportedIssuer();

        $this->handler
            ->handleRunScheduledUpdates([

                'hour' => 2,

            ]);

        $this->assertDatabaseCount(
            'security_ingestion_batch_items',
            1
        );

        $this->assertEquals(
            SecurityUpdateType::FUND_DATA,
            SecurityIngestionBatchItem::firstOrFail()
                ->security_update_type_id
        );

        Queue::assertPushed(
            RunScheduledSecurityUpdateJob::class,
            1
        );
    }

    public function test_it_runs_fund_data_every_day_regardless_of_run_day()
    {
        Queue::fake();

        /*
        |--------------------------------------------------------------------------
        | run_day 7 never matched Carbon's 0-6 dayOfWeek under the old logic
        |--------------------------------------------------------------------------
        */

        SecurityUpdateSchedule::factory()

            ->aum()

            ->scheduledFor(
                7,
                2
            )

            ->create([
                'status_id' => Status::ACTIVE,
            ]);

        $this->travelTo(
            now()->startOfWeek()->addDays(2)->setTime(2, 0)
        );

        $this->assignSupportedIssuer();

        $this->handler
            ->handleRunScheduledUpdates();

        Queue::assertPushed(
            RunScheduledSecurityUpdateJob::class,
            1
        );
    }

    public function test_it_returns_success_when_no_schedules_exist()
    {
        Queue::fake();

        $this->assignSupportedIssuer();

        $result =

            $this->handler
                ->handleRunScheduledUpdates([

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

            ->aum()

            ->scheduledFor(
                1,
                2
            )

            ->create([
                'status_id' => Status::INACTIVE,
            ]);

        $this->assignSupportedIssuer();

        $result =

            $this->handler
                ->handleRunScheduledUpdates([

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

    public function test_it_honors_run_hour()
    {
        Queue::fake();

        SecurityUpdateSchedule::factory()

            ->aum()

            ->scheduledFor(
                1,
                2
            )

            ->create();

        SecurityUpdateSchedule::factory()

            ->aum()

            ->scheduledFor(
                1,
                3
            )

            ->create();

        $this->assignSupportedIssuer();

        $this->handler
            ->handleRunScheduledUpdates([

                'hour' => 2,

            ]);

        $this->assertDatabaseCount(
            'security_ingestion_batch_items',
            1
        );

        Queue::assertPushed(
            RunScheduledSecurityUpdateJob::class,
            1
        );
    }

    private function assignSupportedIssuer(): void
    {
        SecurityDetail::query()->update([
            'etf_issuer_id' => EtfIssuer::YIELDMAX,
        ]);
    }

    public function test_it_skips_securities_whose_issuer_has_no_scraper()
    {
        Queue::fake();

        $supported = SecurityUpdateSchedule::factory()
            ->aum()
            ->scheduledFor(1, 2)
            ->create();

        $unsupported = SecurityUpdateSchedule::factory()
            ->aum()
            ->scheduledFor(1, 2)
            ->create();

        SecurityDetail::where('security_id', $supported->security_id)
            ->update(['etf_issuer_id' => EtfIssuer::YIELDMAX]);

        SecurityDetail::where('security_id', $unsupported->security_id)
            ->update(['etf_issuer_id' => EtfIssuer::DEFIANCE]);

        $this->handler
            ->handleRunScheduledUpdates([

                'hour' => 2,

            ]);

        $this->assertDatabaseCount(
            'security_ingestion_batch_items',
            1
        );

        $this->assertDatabaseHas('security_ingestion_batch_items', [
            'security_id' => $supported->security_id,
        ]);

        Queue::assertPushed(
            RunScheduledSecurityUpdateJob::class,
            1
        );
    }
}
