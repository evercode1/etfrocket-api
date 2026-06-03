<?php

namespace Tests\Unit\AiSignals\TwelveDataStats;

use App\Services\AI\AiSignals\TwelveDataStats\TwelveDataClientService;
use Exception;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwelveDataClientServiceTest extends TestCase
{
    public function test_get_quote_returns_normalized_data(): void
    {
        Http::fake([

            '*' => Http::response([

                'symbol' => 'SPY',

                'close' => '648.52',

                'percent_change' => '0.83',

            ]),

        ]);

        $service = new TwelveDataClientService;

        $data = $service->getQuote(
            'SPY'
        );

        $this->assertEquals(
            'SPY',
            $data['symbol']
        );

        $this->assertEquals(
            648.52,
            $data['price']
        );

        $this->assertEquals(
            0.83,
            $data['change_percent']
        );
    }

    public function test_get_quote_throws_exception_when_api_returns_error(): void
    {
        Http::fake([

            '*' => Http::response([

                'code' => 400,

                'message' => 'Invalid symbol',

            ]),

        ]);

        $this->expectException(
            Exception::class
        );

        $this->expectExceptionMessage(
            'Invalid symbol'
        );

        $service = new TwelveDataClientService;

        $service->getQuote(
            'BAD'
        );
    }

    public function test_get_daily_history_returns_normalized_history(): void
    {
        Http::fake([

            '*' => Http::response([

                'values' => [

                    [

                        'datetime' => '2026-06-02',

                        'close' => '648.52',

                    ],

                    [

                        'datetime' => '2026-06-01',

                        'close' => '645.31',

                    ],

                ],

            ]),

        ]);

        $service = new TwelveDataClientService;

        $history = $service->getDailyHistory(
            'SPY'
        );

        $this->assertCount(
            2,
            $history
        );

        $this->assertEquals(
            '2026-06-02',
            $history[0]['datetime']
        );

        $this->assertEquals(
            648.52,
            $history[0]['close']
        );

        $this->assertEquals(
            '2026-06-01',
            $history[1]['datetime']
        );

        $this->assertEquals(
            645.31,
            $history[1]['close']
        );
    }

    public function test_get_daily_history_returns_empty_array_when_no_values_exist(): void
    {
        Http::fake([

            '*' => Http::response([

                'values' => [],

            ]),

        ]);

        $service = new TwelveDataClientService;

        $history = $service->getDailyHistory(
            'SPY'
        );

        $this->assertEquals(
            [],
            $history
        );
    }

    public function test_get_daily_history_throws_exception_when_api_returns_error(): void
    {
        Http::fake([

            '*' => Http::response([

                'code' => 400,

                'message' => 'Invalid symbol',

            ]),

        ]);

        $this->expectException(
            Exception::class
        );

        $this->expectExceptionMessage(
            'Invalid symbol'
        );

        $service = new TwelveDataClientService;

        $service->getDailyHistory(
            'BAD'
        );
    }
}
