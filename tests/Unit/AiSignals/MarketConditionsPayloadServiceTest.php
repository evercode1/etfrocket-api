<?php

namespace Tests\Unit\AiSignals\Payloads;

use App\Services\AI\AiSignals\Payloads\MarketConditionsPayloadService;
use App\Services\AI\AiSignals\TwelveDataStats\TwelveDataStatsService;
use Mockery;
use Tests\TestCase;

class MarketConditionsPayloadServiceTest extends TestCase
{
    public function test_it_builds_market_conditions_payload(): void
    {
        $telemetry = [

            'generated_at' => now()->toDateTimeString(),

            'indexes' => [

                'SPY' => [

                    'name' => 'S&P 500',

                    'metrics' => [

                        'price' => 120,

                        'fifty_day_ma' => 110,

                        'two_hundred_day_ma' => 100,

                        'three_month_return' => 15,

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

                'XLY' => [

                    'name' => 'Consumer Discretionary',

                    'metrics' => [

                        'one_month_return' => 8,

                    ],

                ],

                'XLC' => [

                    'name' => 'Communication Services',

                    'metrics' => [

                        'one_month_return' => 10,

                    ],

                ],

                'XLU' => [

                    'name' => 'Utilities',

                    'metrics' => [

                        'one_month_return' => 1,

                    ],

                ],

                'XLP' => [

                    'name' => 'Consumer Staples',

                    'metrics' => [

                        'one_month_return' => 2,

                    ],

                ],

                'XLV' => [

                    'name' => 'Healthcare',

                    'metrics' => [

                        'one_month_return' => 3,

                    ],

                ],

            ],

            'bonds' => [

                'TLT' => [

                    'name' => 'Long-Term Treasuries',

                    'metrics' => [

                        'one_month_return' => -4,

                    ],

                ],

                'IEF' => [

                    'name' => 'Intermediate-Term Treasuries',

                    'metrics' => [

                        'one_month_return' => -3,

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

        $service = new MarketConditionsPayloadService(
            $statsService
        );

        $payload = $service->getData();

        $this->assertEquals(
            'bullish',
            $payload['trend']
        );

        $this->assertEquals(
            'strong',
            $payload['momentum']
        );

        $this->assertEquals(
            'normal',
            $payload['vix_regime']
        );

        $this->assertEquals(
            'growth_leading',
            $payload['growth_vs_defensive']
        );

        $this->assertEquals(
            'risk_on',
            $payload['bond_signal']
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
