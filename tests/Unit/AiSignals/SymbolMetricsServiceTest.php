<?php

namespace Tests\Unit\AiSignals\TwelveDataStats;

use App\Services\AI\AiSignals\TwelveDataStats\SymbolMetricsService;
use Tests\TestCase;

class SymbolMetricsServiceTest extends TestCase
{
    public function test_it_calculates_returns_and_moving_averages(): void
    {
        $history = [];

        for ($i = 0; $i < 250; $i++) {

            $history[] = [

                'close' => 100,

            ];
        }

        $history[0]['close'] = 120;

        $service = new SymbolMetricsService;

        $data = $service->getData(
            $history
        );

        $this->assertEquals(
            20.00,
            $data['one_month_return']
        );

        $this->assertEquals(
            20.00,
            $data['three_month_return']
        );

        $this->assertEquals(
            20.00,
            $data['six_month_return']
        );

        $this->assertEquals(
            100.40,
            $data['fifty_day_ma']
        );

        $this->assertEquals(
            100.10,
            $data['two_hundred_day_ma']
        );
    }

    public function test_it_returns_null_when_history_is_insufficient(): void
    {
        $history = [];

        for ($i = 0; $i < 10; $i++) {

            $history[] = [

                'close' => 100,

            ];
        }

        $service = new SymbolMetricsService;

        $data = $service->getData(
            $history
        );

        $this->assertNull(
            $data['one_month_return']
        );

        $this->assertNull(
            $data['three_month_return']
        );

        $this->assertNull(
            $data['six_month_return']
        );

        $this->assertNull(
            $data['fifty_day_ma']
        );

        $this->assertNull(
            $data['two_hundred_day_ma']
        );
    }

    public function test_it_returns_null_when_historical_price_is_zero(): void
    {
        $history = [];

        for ($i = 0; $i < 250; $i++) {

            $history[] = [

                'close' => 100,

            ];
        }

        $history[21]['close'] = 0;

        $service = new SymbolMetricsService;

        $data = $service->getData(
            $history
        );

        $this->assertNull(
            $data['one_month_return']
        );

        $this->assertEquals(
            0.00,
            $data['three_month_return']
        );

        $this->assertEquals(
            0.00,
            $data['six_month_return']
        );
    }
}
