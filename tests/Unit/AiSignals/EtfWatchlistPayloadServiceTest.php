<?php

namespace Tests\Unit\AiSignals\Payloads;

use App\Services\AI\AiSignals\Payloads\EtfWatchlistPayloadService;
use App\Services\AI\AiSignals\Watchlists\EtfWatchlistService;
use Mockery;
use Tests\TestCase;

class EtfWatchlistPayloadServiceTest extends TestCase
{
    public function test_it_builds_etf_watchlist_payload(): void
    {
        $watchlists = [

            'top_performers' => [

                [
                    'symbol' => 'AAA',
                    'metric_value' => 20,
                ],

                [
                    'symbol' => 'BBB',
                    'metric_value' => 15,
                ],

            ],

            'price_movers' => [

                [
                    'symbol' => 'CCC',
                    'metric_value' => 12,
                ],

                [
                    'symbol' => 'DDD',
                    'metric_value' => 8,
                ],

            ],

            'aum_growth' => [

                [
                    'symbol' => 'EEE',
                    'metric_value' => 25,
                ],

                [
                    'symbol' => 'FFF',
                    'metric_value' => 18,
                ],

            ],

            'nav_health' => [

                [
                    'symbol' => 'GGG',
                    'metric_value' => 0,
                ],

                [
                    'symbol' => 'HHH',
                    'metric_value' => -2,
                ],

            ],

        ];

        $watchlistService = Mockery::mock(
            EtfWatchlistService::class
        );

        $watchlistService

            ->shouldReceive('getData')

            ->once()

            ->andReturn(
                $watchlists
            );

        $service = new EtfWatchlistPayloadService(
            $watchlistService
        );

        $payload = $service->getData();

        $this->assertArrayHasKey(
            'generated_at',
            $payload
        );

        $this->assertArrayHasKey(
            'top_performers',
            $payload
        );

        $this->assertArrayHasKey(
            'price_movers',
            $payload
        );

        $this->assertArrayHasKey(
            'aum_growth',
            $payload
        );

        $this->assertArrayHasKey(
            'nav_health',
            $payload
        );

        $this->assertArrayHasKey(
            'watchlist_summary',
            $payload
        );

        $this->assertEquals(
            'AAA',
            $payload['watchlist_summary']['strongest_performer']['symbol']
        );

        $this->assertEquals(
            'CCC',
            $payload['watchlist_summary']['strongest_price_mover']['symbol']
        );

        $this->assertEquals(
            'EEE',
            $payload['watchlist_summary']['strongest_aum_growth']['symbol']
        );

        $this->assertEquals(
            'GGG',
            $payload['watchlist_summary']['strongest_nav_health']['symbol']
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
