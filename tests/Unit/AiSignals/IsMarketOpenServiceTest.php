<?php

namespace Tests\Unit\AiSignals;

use App\Services\AI\AiSignals\IsMarketOpenService;
use Carbon\Carbon;
use Tests\TestCase;

class IsMarketOpenServiceTest extends TestCase
{
    private IsMarketOpenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service =
            new IsMarketOpenService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_true_during_market_hours()
    {
        Carbon::setTestNow(

            Carbon::parse(
                '2026-06-15 10:00:00',
                'America/New_York'
            )

        );

        $this->assertTrue(

            $this->service->isOpen()

        );
    }

    public function test_it_returns_false_before_market_open()
    {
        Carbon::setTestNow(

            Carbon::parse(
                '2026-06-15 08:00:00',
                'America/New_York'
            )

        );

        $this->assertFalse(

            $this->service->isOpen()

        );
    }

    public function test_it_returns_false_after_market_close()
    {
        Carbon::setTestNow(

            Carbon::parse(
                '2026-06-15 17:00:00',
                'America/New_York'
            )

        );

        $this->assertFalse(

            $this->service->isOpen()

        );
    }

    public function test_it_returns_false_on_saturday()
    {
        Carbon::setTestNow(

            Carbon::parse(
                '2026-06-13 12:00:00',
                'America/New_York'
            )

        );

        $this->assertFalse(

            $this->service->isOpen()

        );
    }

    public function test_it_returns_false_on_sunday()
    {
        Carbon::setTestNow(

            Carbon::parse(
                '2026-06-14 12:00:00',
                'America/New_York'
            )

        );

        $this->assertFalse(

            $this->service->isOpen()

        );
    }

    public function test_it_returns_false_on_new_years_day()
    {
        Carbon::setTestNow(

            Carbon::parse(
                '2026-01-01 12:00:00',
                'America/New_York'
            )

        );

        $this->assertFalse(

            $this->service->isOpen()

        );
    }

    public function test_it_returns_false_on_thanksgiving()
    {
        Carbon::setTestNow(

            Carbon::parse(
                '2026-11-26 12:00:00',
                'America/New_York'
            )

        );

        $this->assertFalse(

            $this->service->isOpen()

        );
    }

    public function test_it_returns_false_on_christmas()
    {
        Carbon::setTestNow(

            Carbon::parse(
                '2027-12-24 12:00:00',
                'America/New_York'
            )

        );

        $this->assertFalse(

            $this->service->isOpen()

        );
    }

    public function test_it_returns_true_on_regular_trading_day()
    {
        Carbon::setTestNow(

            Carbon::parse(
                '2027-08-10 13:00:00',
                'America/New_York'
            )

        );

        $this->assertTrue(

            $this->service->isOpen()

        );
    }

    public function test_it_returns_false_exactly_at_market_close()
    {
        Carbon::setTestNow(

            Carbon::parse(
                '2026-06-15 16:00:01',
                'America/New_York'
            )

        );

        $this->assertFalse(

            $this->service->isOpen()

        );
    }
}
