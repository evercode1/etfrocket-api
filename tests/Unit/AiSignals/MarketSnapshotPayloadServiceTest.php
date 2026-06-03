<?php

namespace Tests\Unit\AiSignals\Payloads;

use App\Services\AI\AiSignals\Payloads\MarketSnapshotPayloadService;
use App\Services\AI\AiSignals\TwelveDataStats\TwelveDataStatsService;
use Mockery;
use Tests\TestCase;

class MarketSnapshotPayloadServiceTest extends TestCase
{
    public function test_it_builds_market_snapshot_payload(): void
    {
        $telemetry = [

            'generated_at' => now()->toDateTimeString(),

            'indexes' => [

                'SPY' => [

                    'name' => 'S&P 500',

                    'metrics' => [

                        'one_month_return' => 5,

                    ],

                ],

                'QQQ' => [

                    'name' => 'Nasdaq 100',

                    'metrics' => [

                        'one_month_return' => 10,

                    ],

                ],

                'IWM' => [

                    'name' => 'Russell 2000',

                    'metrics' => [

                        'one_month_return' => -2,

                    ],

                ],

            ],

            'sectors' => [

                'XLK' => [

                    'name' => 'Technology',

                    'metrics' => [

                        'one_month_return' => 12,

                    ],

                ],

                'XLF' => [

                    'name' => 'Financials',

                    'metrics' => [

                        'one_month_return' => 8,

                    ],

                ],

                'XLI' => [

                    'name' => 'Industrials',

                    'metrics' => [

                        'one_month_return' => 6,

                    ],

                ],

                'XLU' => [

                    'name' => 'Utilities',

                    'metrics' => [

                        'one_month_return' => -4,

                    ],

                ],

                'XLRE' => [

                    'name' => 'Real Estate',

                    'metrics' => [

                        'one_month_return' => -2,

                    ],

                ],

                'XLP' => [

                    'name' => 'Consumer Staples',

                    'metrics' => [

                        'one_month_return' => 0,

                    ],

                ],

            ],

            'leadership' => [],

        ];

        $statsService = Mockery::mock(
            TwelveDataStatsService::class
        );

        $statsService

            ->shouldReceive('getData')

            ->once()

            ->andReturn(
                $telemetry
            );

        $service = new MarketSnapshotPayloadService(
            $statsService
        );

        $payload = $service->getData();

        $this->assertArrayHasKey(
            'generated_at',
            $payload
        );

        $this->assertArrayHasKey(
            'top_sectors',
            $payload
        );

        $this->assertArrayHasKey(
            'bottom_sectors',
            $payload
        );

        $this->assertArrayHasKey(
            'best_index',
            $payload
        );

        $this->assertArrayHasKey(
            'worst_index',
            $payload
        );

        $this->assertEquals(
            'Technology',
            $payload['top_sectors'][0]['name']
        );

        $this->assertEquals(
            'Financials',
            $payload['top_sectors'][1]['name']
        );

        $this->assertEquals(
            'Industrials',
            $payload['top_sectors'][2]['name']
        );

        $this->assertEquals(
            'Utilities',
            $payload['bottom_sectors'][0]['name']
        );

        $this->assertEquals(
            'Real Estate',
            $payload['bottom_sectors'][1]['name']
        );

        $this->assertEquals(
            'Consumer Staples',
            $payload['bottom_sectors'][2]['name']
        );

        $this->assertEquals(
            'Nasdaq 100',
            $payload['best_index']['name']
        );

        $this->assertEquals(
            10,
            $payload['best_index']['one_month_return']
        );

        $this->assertEquals(
            'Russell 2000',
            $payload['worst_index']['name']
        );

        $this->assertEquals(
            -2,
            $payload['worst_index']['one_month_return']
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
