<?php

namespace Tests\Unit\PortfolioStats;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Models\User;
use App\Services\PortfolioStats\PortfolioHistoricalStatsCollectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioHistoricalStatsCollectionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('statuses')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('statuses')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_holdings_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('NVII');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $security->id, 1, 5, 30, '2026-02-01');
        $this->createTransaction($portfolio->id, $security->id, 2, 3, 35, '2026-03-01');

        $transactions = PortfolioTransaction::where(
            'portfolio_id',
            $portfolio->id
        )->get();

        $holdings = app(
            PortfolioHistoricalStatsCollectionService::class
        )->getHoldingsAsOfDate(
            $transactions,
            '2026-02-15'
        );

        $this->assertCount(1, $holdings);
        $this->assertSame($security->id, $holdings->first()['security_id']);
        $this->assertSame(15.0, $holdings->first()['shares']);
    }

    public function test_it_calculates_shares_owned_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('NVII');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $security->id, 1, 5, 30, '2026-02-01');
        $this->createTransaction($portfolio->id, $security->id, 2, 4, 35, '2026-03-01');

        $transactions = PortfolioTransaction::where(
            'portfolio_id',
            $portfolio->id
        )->get();

        $shares = app(
            PortfolioHistoricalStatsCollectionService::class
        )->getSharesOwnedAsOfDate(
            $transactions,
            $security->id,
            '2026-02-15'
        );

        $this->assertSame(15.0, $shares);
    }

    public function test_it_calculates_portfolio_value_as_of_date(): void
    {
        $portfolio = $this->createPortfolio();

        $firstSecurity = $this->createSecurity('NVII');
        $secondSecurity = $this->createSecurity('JEPI');

        $this->createTransaction($portfolio->id, $firstSecurity->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $secondSecurity->id, 1, 5, 50, '2026-01-01');

        $this->createPrice($firstSecurity->id, '2026-01-15', 30);
        $this->createPrice($secondSecurity->id, '2026-01-15', 40);

        $transactions = PortfolioTransaction::where(
            'portfolio_id',
            $portfolio->id
        )->get();

        $prices = SecurityPriceHistory::all()
            ->groupBy(
                'security_id'
            );

        $value = app(
            PortfolioHistoricalStatsCollectionService::class
        )->getPortfolioValueAsOfDate(
            $transactions,
            $prices,
            '2026-01-31'
        );

        $this->assertSame(500.0, $value);
    }

    public function test_it_calculates_dividend_income_between_dates_using_shares_owned_on_ex_date(): void
    {
        $portfolio = $this->createPortfolio();

        $security = $this->createSecurity('DIV');

        $this->createTransaction($portfolio->id, $security->id, 1, 10, 25, '2026-01-01');
        $this->createTransaction($portfolio->id, $security->id, 1, 5, 30, '2026-01-20');

        $this->createDividend($security->id, '0.5000', '2026-01-15');
        $this->createDividend($security->id, '1.0000', '2026-01-25');

        $transactions = PortfolioTransaction::where(
            'portfolio_id',
            $portfolio->id
        )->get();

        $dividendsBySecurity = SecurityDividendHistory::all()
            ->groupBy(
                'security_id'
            );

        $income = app(

            PortfolioHistoricalStatsCollectionService::class

        )->getDividendIncomeBetweenDates(

            $transactions,

            $dividendsBySecurity,

            '2026-01-01',

            '2026-01-31'

        );

        $this->assertSame(20.0, $income);
    }

    private function createPortfolio(): Portfolio
    {
        $user = User::factory()->create();

        return Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);
    }

    private function createSecurity(string $symbol): Security
    {
        return Security::factory()->create([
            'symbol' => $symbol,

            'status_id' => Status::ACTIVE,

        ]);
    }

    private function createTransaction(
        int $portfolioId,
        int $securityId,
        int $transactionTypeId,
        float $shares,
        float $pricePerShare,
        string $transactionDate
    ): PortfolioTransaction {
        return PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolioId,
            'security_id' => $securityId,
            'transaction_type_id' => $transactionTypeId,
            'shares' => $shares,
            'price_per_share' => $pricePerShare,
            'transaction_date' => $transactionDate,
        ]);
    }

    private function createDividend(
        int $securityId,
        string $amount,
        string $exDividendDate
    ): SecurityDividendHistory {
        return SecurityDividendHistory::factory()->create([
            'security_id' => $securityId,
            'dividend_amount' => $amount,
            'ex_dividend_date' => $exDividendDate,
            'payment_date' => Carbon::parse($exDividendDate)->addDay()->toDateString(),
            'data_source_id' => 1,
        ]);
    }

    private function createPrice(
        int $securityId,
        string $date,
        float $closePrice
    ): SecurityPriceHistory {
        return SecurityPriceHistory::factory()->create([
            'security_id' => $securityId,
            'price_date' => $date,
            'close_price' => $closePrice,
            'data_source_id' => 1,
        ]);
    }
}
