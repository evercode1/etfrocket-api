<?php

namespace Tests\Unit\AiSignals\TwelveDataStats;

use App\Services\AI\AiSignals\TwelveDataStats\SymbolMetricsService;
use App\Services\AI\AiSignals\TwelveDataStats\TwelveDataClientService;
use App\Services\AI\AiSignals\TwelveDataStats\TwelveDataStatsService;
use Mockery;
use Tests\TestCase;

class TwelveDataStatsServiceTest extends TestCase
{
    public function test_it_builds_market_telemetry_data(): void
    {
        config()->set(
            'ai-signals.indexes',
            [
                'SPY' => 'S&P 500',
            ]
        );

        config()->set(
            'ai-signals.sectors',
            []
        );

        config()->set(
            'ai-signals.bonds',
            []
        );

        config()->set(
            'ai-signals.leadership',
            []
        );

        $client = Mockery::mock(
            TwelveDataClientService::class
        );

        $client

            ->shouldReceive('getQuote')

            ->once()

            ->with('SPY')

            ->andReturn([

                'symbol' => 'SPY',

                'price' => 648.52,

                'change_percent' => 0.83,

            ]);

        $client

            ->shouldReceive('getDailyHistory')

            ->once()

            ->andReturn([

                [

                    'datetime' => '2026-06-02',

                    'close' => 648.52,

                ],

            ]);

        $metricsService = Mockery::mock(
            SymbolMetricsService::class
        );

        $metricsService

            ->shouldReceive('getData')

            ->once()

            ->andReturn([

                'one_month_return' => 4.21,

                'three_month_return' => 8.11,

                'six_month_return' => 12.40,

                'fifty_day_ma' => 625.12,

                'two_hundred_day_ma' => 590.88,

            ]);

        $service = new TwelveDataStatsService(
            $client,
            $metricsService
        );

        $data = $service->getData();

        $this->assertArrayHasKey(
            'generated_at',
            $data
        );

        $this->assertArrayHasKey(
            'indexes',
            $data
        );

        $this->assertArrayHasKey(
            'SPY',
            $data['indexes']
        );

        $this->assertEquals(
            'S&P 500',
            $data['indexes']['SPY']['name']
        );

        $this->assertEquals(
            648.52,
            $data['indexes']['SPY']['metrics']['price']
        );

        $this->assertEquals(
            0.83,
            $data['indexes']['SPY']['metrics']['change_percent']
        );

        $this->assertEquals(
            4.21,
            $data['indexes']['SPY']['metrics']['one_month_return']
        );

        $this->assertEquals(
            625.12,
            $data['indexes']['SPY']['metrics']['fifty_day_ma']
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
