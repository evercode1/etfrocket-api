<?php

namespace Tests\Feature\Console;

use Mockery;
use Tests\TestCase;
use App\Models\Etf;
use App\Models\AiDataExtraction;
use Illuminate\Support\Facades\DB;
use App\Services\AI\Extractions\AiEtfDataExtractionService;
use App\Services\AI\Extractions\ProcessAiEtfDataExtractionService;
use Database\Seeders\EtfSeeder;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\NotificationStatusSeeder;


class RunAiEtfDataExtractionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_aum_histories')->truncate();
        DB::table('etf_nav_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('ai_data_extractions')->truncate();
        DB::table('etfs')->truncate();
        DB::table('intervals')->truncate();
        DB::table('statuses')->truncate();
        DB::table('notification_statuses')->truncate();

        $this->seed([

            IntervalSeeder::class,
            EtfSeeder::class,
            StatusSeeder::class,
            NotificationStatusSeeder::class,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_aum_histories')->truncate();
        DB::table('etf_nav_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('ai_data_extractions')->truncate();
        DB::table('etfs')->truncate();
        DB::table('intervals')->truncate();
        DB::table('statuses')->truncate();
        DB::table('notification_statuses')->truncate();
        parent::tearDown();
    }

    public function test_it_runs_ai_extraction_for_a_single_symbol()
    {
        $etf = Etf::where('symbol', 'CHPY')->firstOrFail();

        $extraction = AiDataExtraction::factory()->create([
            'etf_id' => $etf->id,
            'extracted_data' => [
                'symbol' => $etf->symbol,
            ],
        ]);

        $aiService = Mockery::mock(AiEtfDataExtractionService::class);
        $aiService->shouldReceive('extract')
            ->once()
            ->with(Mockery::on(fn($passedEtf) => $passedEtf->id === $etf->id))
            ->andReturn($extraction);

        $processService = Mockery::mock(ProcessAiEtfDataExtractionService::class);
        $processService->shouldReceive('process')
            ->once()
            ->with($extraction)
            ->andReturn($extraction);

        $this->app->instance(AiEtfDataExtractionService::class, $aiService);
        $this->app->instance(ProcessAiEtfDataExtractionService::class, $processService);

        $this->artisan('etfs:run-ai-extraction', [
            '--symbol' => 'CHPY',
        ])

            ->assertExitCode(0);
    }

    public function test_it_respects_the_limit_option()
    {
        $etfs = Etf::query()
            ->orderBy('symbol')
            ->limit(2)
            ->get();

        $aiService = Mockery::mock(AiEtfDataExtractionService::class);
        $processService = Mockery::mock(ProcessAiEtfDataExtractionService::class);

        foreach ($etfs as $etf) {
            $extraction = AiDataExtraction::factory()->create([
                'etf_id' => $etf->id,
                'extracted_data' => [
                    'symbol' => $etf->symbol,
                ],
            ]);

            $aiService->shouldReceive('extract')
                ->once()
                ->with(Mockery::on(fn($passedEtf) => $passedEtf->id === $etf->id))
                ->andReturn($extraction);

            $processService->shouldReceive('process')
                ->once()
                ->with($extraction)
                ->andReturn($extraction);
        }

        $this->app->instance(AiEtfDataExtractionService::class, $aiService);
        $this->app->instance(ProcessAiEtfDataExtractionService::class, $processService);

        $this->artisan('etfs:run-ai-extraction', [
            '--limit' => 2,
        ])

            ->assertExitCode(0);
    }

    public function test_it_returns_success_when_no_etfs_are_found()
    {
        $this->app->instance(
            AiEtfDataExtractionService::class,
            Mockery::mock(AiEtfDataExtractionService::class)
        );

        $this->app->instance(
            ProcessAiEtfDataExtractionService::class,
            Mockery::mock(ProcessAiEtfDataExtractionService::class)
        );

        $this->artisan('etfs:run-ai-extraction', [
            '--symbol' => 'NOTREAL',
        ])

            ->assertExitCode(0);
    }

    public function test_it_continues_processing_when_one_etf_fails()
    {
        $etfs = Etf::query()
            ->orderBy('symbol')
            ->limit(2)
            ->get();

        $firstEtf = $etfs[0];
        $secondEtf = $etfs[1];

        $secondExtraction = AiDataExtraction::factory()->create([
            'etf_id' => $secondEtf->id,
            'extracted_data' => [
                'symbol' => $secondEtf->symbol,
            ],
        ]);

        $aiService = Mockery::mock(AiEtfDataExtractionService::class);
        $processService = Mockery::mock(ProcessAiEtfDataExtractionService::class);

        $aiService->shouldReceive('extract')
            ->once()
            ->with(Mockery::on(fn($passedEtf) => $passedEtf->id === $firstEtf->id))
            ->andThrow(new \RuntimeException('AI failed'));

        $aiService->shouldReceive('extract')
            ->once()
            ->with(Mockery::on(fn($passedEtf) => $passedEtf->id === $secondEtf->id))
            ->andReturn($secondExtraction);

        $processService->shouldReceive('process')
            ->once()
            ->with($secondExtraction)
            ->andReturn($secondExtraction);

        $this->app->instance(AiEtfDataExtractionService::class, $aiService);
        $this->app->instance(ProcessAiEtfDataExtractionService::class, $processService);

        $this->artisan('etfs:run-ai-extraction', [
            '--limit' => 2,
        ])

            ->assertExitCode(0);
    }
}
