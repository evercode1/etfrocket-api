<?php

namespace Tests\Unit\Queries\Securities;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityDividendHistory;
use App\Queries\Securities\SecurityDividendHistoryQuery;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SecurityDividendHistoryQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        SecurityDividendHistory::truncate();
        Security::truncate();
        SecurityDetail::truncate();
    }

    protected function tearDown(): void
    {
        SecurityDividendHistory::truncate();
        Security::truncate();
        SecurityDetail::truncate();

        parent::tearDown();
    }

    public function test_it_returns_empty_array_when_no_dividend_history_exists()
    {
        $security = Security::factory()->create();

        $results = app(
            SecurityDividendHistoryQuery::class
        )->getData(
            $security->id
        );

        $this->assertIsArray($results);

        $this->assertCount(
            0,
            $results
        );
    }

    public function test_it_returns_dividend_history_for_security()
    {
        $security = Security::factory()->create();

        $dividend = SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => 0.2500,
            'ex_dividend_date' => '2025-03-15',
            'payment_date' => '2025-03-31',
        ]);

        $results = app(
            SecurityDividendHistoryQuery::class
        )->getData(
            $security->id
        );

        $this->assertCount(
            1,
            $results
        );

        $this->assertEquals(
            $dividend->id,
            $results[0]['id']
        );

        $this->assertEquals(
            0.2500,
            $results[0]['dividend_amount']
        );

        $this->assertEquals(
            '2025-03-15',
            $results[0]['ex_dividend_date']
        );

        $this->assertEquals(
            '2025-03-31',
            $results[0]['payment_date']
        );
    }

    public function test_it_returns_only_dividends_for_requested_security()
    {
        $security = Security::factory()->create();

        $otherSecurity = Security::factory()->create();

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $otherSecurity->id,
        ]);

        $results = app(
            SecurityDividendHistoryQuery::class
        )->getData(
            $security->id
        );

        $this->assertCount(
            1,
            $results
        );

        $this->assertEquals(
            $security->id,
            SecurityDividendHistory::find(
                $results[0]['id']
            )->security_id
        );
    }

    public function test_it_returns_dividends_in_descending_ex_dividend_date_order()
    {
        $security = Security::factory()->create();

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'ex_dividend_date' => '2024-01-01',
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'ex_dividend_date' => '2025-01-01',
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'ex_dividend_date' => '2023-01-01',
        ]);

        $results = app(
            SecurityDividendHistoryQuery::class
        )->getData(
            $security->id
        );

        $this->assertEquals(
            '2025-01-01',
            $results[0]['ex_dividend_date']
        );

        $this->assertEquals(
            '2024-01-01',
            $results[1]['ex_dividend_date']
        );

        $this->assertEquals(
            '2023-01-01',
            $results[2]['ex_dividend_date']
        );
    }
}
