<?php

namespace Tests\Unit\Commands;

use App\Jobs\RunAiEtfDividendExtractionJob;
use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Models\EtfDividendHistory;
use Database\Seeders\EtfSeeder;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunAiEtfDividendExtractionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('jobs')->truncate();

        DB::table('failed_jobs')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etf_dividend_histories')->truncate();

        DB::table('etfs')->truncate();

        DB::table('intervals')->truncate();

        DB::table('statuses')->truncate();

        DB::table('notification_statuses')->truncate();

        $this->seed([

            IntervalSeeder::class,

            StatusSeeder::class,

            NotificationStatusSeeder::class,

            EtfSeeder::class,

        ]);

        Queue::fake();
    }

    protected function tearDown(): void
    {
        DB::table('jobs')->truncate();

        DB::table('failed_jobs')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('etf_dividend_histories')->truncate();

        DB::table('etfs')->truncate();

        DB::table('intervals')->truncate();

        DB::table('statuses')->truncate();

        DB::table('notification_statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_dispatches_jobs_for_all_etfs()
    {
        $etfCount =
            Etf::count();

        $this->artisan(
            'etfs:run-ai-dividend-extraction'
        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiEtfDividendExtractionJob::class,
            $etfCount
        );
    }

    public function test_it_dispatches_job_for_single_symbol()
    {
        $etf =
            Etf::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $this->artisan(

            'etfs:run-ai-dividend-extraction',

            [

                '--symbol' =>
                'CHPY',

            ]

        )->assertExitCode(0);

        Queue::assertPushed(

            RunAiEtfDividendExtractionJob::class,

            function ($job) use ($etf) {

                return
                    $job->etfId ===
                    $etf->id;
            }

        );

        Queue::assertPushed(
            RunAiEtfDividendExtractionJob::class,
            1
        );
    }

    public function test_it_respects_limit_option()
    {
        $this->artisan(

            'etfs:run-ai-dividend-extraction',

            [

                '--limit' => 5,

            ]

        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiEtfDividendExtractionJob::class,
            5
        );
    }

    public function test_force_flag_bypasses_freshness_check()
    {
        EtfDividendHistory::create([

            'etf_id' =>
            Etf::first()->id,

            'dividend_amount' =>
            0.25,

            'ex_dividend_date' =>
            now()->toDateString(),

            'payment_date' =>
            now()->addDays(7)->toDateString(),

            'data_source_id' => 1,

            'retrieved_at' =>
            now(),

        ]);

        AiDataExtraction::factory()
            ->create([

                'created_at' =>
                now(),

            ]);

        $this->artisan(

            'etfs:run-ai-dividend-extraction',

            [

                '--force' => true,

                '--limit' => 2,

            ]

        )->assertExitCode(0);

        Queue::assertPushed(
            RunAiEtfDividendExtractionJob::class,
            2
        );
    }

    public function test_it_skips_dispatch_when_dividend_data_is_fresh()
    {
        EtfDividendHistory::create([

            'etf_id' =>
            Etf::first()->id,

            'dividend_amount' =>
            0.25,

            'ex_dividend_date' =>
            now()->toDateString(),

            'payment_date' =>
            now()->addDays(7)->toDateString(),

            'data_source_id' => 1,

            'retrieved_at' =>
            now(),

        ]);

        AiDataExtraction::factory()
            ->create([

                'created_at' =>
                now(),

            ]);

        $this->artisan(
            'etfs:run-ai-dividend-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_it_returns_success_when_no_etfs_exist()
    {
        DB::table('etfs')
            ->truncate();

        $this->artisan(
            'etfs:run-ai-dividend-extraction'
        )->assertExitCode(0);

        Queue::assertNothingPushed();
    }
}
