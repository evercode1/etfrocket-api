<?php

namespace Tests\Unit\Queries\Securities;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Queries\Securities\SecuritiesHoverCardQuery;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecuritiesHoverCardQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('security_details')->truncate();
        DB::table('securities')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('security_details')->truncate();
        DB::table('securities')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_hover_card_data(): void
    {
        $security = Security::create([
            'symbol' => 'AMDY',
            'security_type_id' => 1,
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'security_name' => 'AMD Option Income ETF',
            'distribution_frequency_id' => 2,
            'etf_issuer_id' => 1,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-20',
            'close_price' => 18.42,
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => 0.52,
            'ex_dividend_date' => '2026-05-23',
            'payment_date' => '2026-05-30',
            'data_source_id' => 1,
        ]);

        $result = app(
            SecuritiesHoverCardQuery::class
        )->getData('AMDY');

        $this->assertSame(
            'AMDY',
            $result['symbol']
        );

        $this->assertSame(
            'AMD Option Income ETF',
            $result['security_name']
        );

        $this->assertSame(
            18.42,
            (float) $result['last_close_price']
        );

        $this->assertSame(
            '2026-05-23',
            (string) $result['last_ex_dividend_date']
        );

        $this->assertSame(
            0.52,
            (float) $result['last_dividend_amount']
        );

        $this->assertSame(
            'https://finance.yahoo.com/quote/AMDY/',
            $result['yahoo_finance_url']
        );
    }

    public function test_it_returns_null_dividend_data_when_no_dividend_history_exists(): void
    {
        $security = Security::create([
            'symbol' => 'AMDY',
            'security_type_id' => 1,
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'security_name' => 'AMD Option Income ETF',
            'distribution_frequency_id' => 2,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-20',
            'close_price' => 18.42,
            'data_source_id' => 1,
        ]);

        $result = app(
            SecuritiesHoverCardQuery::class
        )->getData('AMDY');

        $this->assertNull(
            $result['last_dividend_amount']
        );

        $this->assertNull(
            $result['last_ex_dividend_date']
        );
    }

    public function test_it_throws_exception_when_symbol_does_not_exist(): void
    {
        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            SecuritiesHoverCardQuery::class
        )->getData('DOESNOTEXIST');
    }
}
