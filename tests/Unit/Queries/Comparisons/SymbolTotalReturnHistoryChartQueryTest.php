<?php

namespace Tests\Unit\Queries\Comparisons;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;
use App\Queries\Comparisons\SymbolTotalReturnHistoryChartQuery;
use Tests\TestCase;

class SymbolTotalReturnHistoryChartQueryTest extends TestCase
{
    private SymbolTotalReturnHistoryChartQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        SecurityPriceHistory::truncate();
        SecurityDividendHistory::truncate();
        SecurityDetail::truncate();
        Security::truncate();

        $this->query = app(SymbolTotalReturnHistoryChartQuery::class);
    }

    protected function tearDown(): void
    {
        SecurityPriceHistory::truncate();
        SecurityDividendHistory::truncate();
        SecurityDetail::truncate();
        Security::truncate();

        parent::tearDown();
    }

    public function test_it_calculates_cumulative_total_return_from_price_and_dividends(): void
    {
        $security = Security::factory()->create([
            'symbol' => 'AMDY',
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-04-01',
            'close_price' => 100.0000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-04-02',
            'close_price' => 90.0000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-04-03',
            'close_price' => 110.0000,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-04-02',
            'dividend_amount' => 10.0000,
        ]);

        $rows = $this->query->getData(
            securityIds: [$security->id],
            startDate: '2026-04-01'
        );

        $this->assertCount(3, $rows);

        $this->assertSame(
            [
                'date' => '2026-04-01',
                'AMDY' => 0.0,
            ],
            $rows[0]
        );

        $this->assertSame(
            [
                'date' => '2026-04-02',
                'AMDY' => 0.0,
            ],
            $rows[1]
        );

        $this->assertSame(
            [
                'date' => '2026-04-03',
                'AMDY' => 20.0,
            ],
            $rows[2]
        );
    }

    public function test_it_accumulates_multiple_dividends(): void
    {
        $security = Security::factory()->create([
            'symbol' => 'CHPY',
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-01',
            'close_price' => 50.0000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-02',
            'close_price' => 48.0000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-03',
            'close_price' => 49.0000,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-05-02',
            'dividend_amount' => 2.0000,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-05-03',
            'dividend_amount' => 1.0000,
        ]);

        $rows = $this->query->getData(
            securityIds: [$security->id],
            startDate: '2026-05-01'
        );

        $this->assertCount(3, $rows);

        $this->assertSame(
            [
                'date' => '2026-05-01',
                'CHPY' => 0.0,
            ],
            $rows[0]
        );

        $this->assertSame(
            [
                'date' => '2026-05-02',
                'CHPY' => 0.0,
            ],
            $rows[1]
        );

        $this->assertSame(
            [
                'date' => '2026-05-03',
                'CHPY' => 4.0,
            ],
            $rows[2]
        );
    }

    public function test_it_calculates_each_security_independently(): void
    {
        $amdy = Security::factory()->create([
            'symbol' => 'AMDY',
        ]);

        $chpy = Security::factory()->create([
            'symbol' => 'CHPY',
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $amdy->id,
            'price_date' => '2026-06-01',
            'close_price' => 100.0000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $amdy->id,
            'price_date' => '2026-06-02',
            'close_price' => 110.0000,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $amdy->id,
            'ex_dividend_date' => '2026-06-02',
            'dividend_amount' => 5.0000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $chpy->id,
            'price_date' => '2026-06-01',
            'close_price' => 50.0000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $chpy->id,
            'price_date' => '2026-06-02',
            'close_price' => 45.0000,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $chpy->id,
            'ex_dividend_date' => '2026-06-02',
            'dividend_amount' => 2.5000,
        ]);

        $rows = $this->query->getData(
            securityIds: [
                $amdy->id,
                $chpy->id,
            ],
            startDate: '2026-06-01'
        );

        $this->assertCount(2, $rows);

        $this->assertSame(
            [
                'date' => '2026-06-01',
                'AMDY' => 0.0,
                'CHPY' => 0.0,
            ],
            $rows[0]
        );

        $this->assertSame(
            [
                'date' => '2026-06-02',
                'AMDY' => 15.0,
                'CHPY' => -5.0,
            ],
            $rows[1]
        );
    }

    public function test_it_excludes_price_history_before_the_start_date(): void
    {
        $security = Security::factory()->create([
            'symbol' => 'AMDY',
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-01-01',
            'close_price' => 80.0000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-04-01',
            'close_price' => 100.0000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-04-02',
            'close_price' => 110.0000,
        ]);

        $rows = $this->query->getData(
            securityIds: [$security->id],
            startDate: '2026-04-01'
        );

        $this->assertCount(2, $rows);

        $this->assertSame(
            [
                'date' => '2026-04-01',
                'AMDY' => 0.0,
            ],
            $rows[0]
        );

        $this->assertSame(
            [
                'date' => '2026-04-02',
                'AMDY' => 10.0,
            ],
            $rows[1]
        );
    }

    public function test_it_excludes_dividends_before_the_start_date(): void
    {
        $security = Security::factory()->create([
            'symbol' => 'AMDY',
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-04-01',
            'close_price' => 100.0000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-04-02',
            'close_price' => 100.0000,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-03-31',
            'dividend_amount' => 25.0000,
        ]);

        $rows = $this->query->getData(
            securityIds: [$security->id],
            startDate: '2026-04-01'
        );

        $this->assertCount(2, $rows);

        $this->assertSame(
            [
                'date' => '2026-04-01',
                'AMDY' => 0.0,
            ],
            $rows[0]
        );

        $this->assertSame(
            [
                'date' => '2026-04-02',
                'AMDY' => 0.0,
            ],
            $rows[1]
        );
    }

    public function test_it_omits_securities_without_price_history(): void
    {
        $securityWithPrices = Security::factory()->create([
            'symbol' => 'AMDY',
        ]);

        $securityWithoutPrices = Security::factory()->create([
            'symbol' => 'CHPY',
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $securityWithPrices->id,
            'price_date' => '2026-04-01',
            'close_price' => 100.0000,
        ]);

        $rows = $this->query->getData(
            securityIds: [
                $securityWithPrices->id,
                $securityWithoutPrices->id,
            ],
            startDate: '2026-04-01'
        );

        $this->assertCount(1, $rows);

        $this->assertSame(
            [
                'date' => '2026-04-01',
                'AMDY' => 0.0,
            ],
            $rows[0]
        );

        $this->assertArrayHasKey('AMDY', $rows[0]);

        $this->assertArrayNotHasKey('CHPY', $rows[0]);
    }

    public function test_it_returns_an_empty_array_when_no_security_ids_are_provided(): void
    {
        $rows = $this->query->getData(
            securityIds: [],
            startDate: '2026-04-01'
        );

        $this->assertSame([], $rows);
    }
}
