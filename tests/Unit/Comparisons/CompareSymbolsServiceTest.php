<?php

namespace Tests\Unit\Comparisons;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\EtfPriceHistory;
use App\Models\PerformanceRangeType;
use App\Services\Comparisons\CompareSymbolsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompareSymbolsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_price_histories')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();
    }

    public function tearDown(): void
    {
        DB::table('etf_price_histories')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_comparison_data()
    {
        $etf = Etf::factory()->create([

            'symbol' => 'CHPY',

            'fund_name' => 'CHPY Test ETF',

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'close_price' => 55.12,

        ]);

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            PerformanceRangeType::THIRTY_DAY,

            'aum_change_percentage' => 12.50,

        ]);

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'total_return_percentage' => 24.80,

        ]);

        $data = (new CompareSymbolsService)->getData(

            symbols: ['CHPY']

        );

        $this->assertEquals(
            1,
            $data['summary']['compared_etfs_count']
        );

        $this->assertEquals(
            'CHPY',
            $data['table_rows'][0]['symbol']
        );

        $this->assertEquals(
            55.12,
            $data['table_rows'][0]['latest_price']
        );

        $this->assertEquals(
            12.50,
            $data['table_rows'][0]['aum_change_percentage_30_day']
        );

        $this->assertEquals(
            24.80,
            $data['table_rows'][0]['total_return_percentage_90_day']
        );
    }
}
