<?php

namespace Tests\Unit\Queries\Etfs;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Queries\Etfs\FilteredEtfsQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FilteredEtfsQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_etfs_sorted_by_selected_metric_descending(): void
    {
        Carbon::setTestNow('2026-05-15');

        $lowEtf = Etf::factory()->create(['symbol' => 'LOW']);
        $highEtf = Etf::factory()->create(['symbol' => 'HIGH']);
        $middleEtf = Etf::factory()->create(['symbol' => 'MID']);

        $this->createMetric($lowEtf, ['total_return_percentage' => 5.25]);
        $this->createMetric($highEtf, ['total_return_percentage' => 22.75]);
        $this->createMetric($middleEtf, ['total_return_percentage' => 12.50]);

        $results = (new FilteredEtfsQuery)->getData([
            'column' => 'total_return_percentage',
            'sort_direction' => 'desc',
            'scope' => 'all',
            'days' => 365,
            'per_page' => 25,
        ]);

        $this->assertCount(3, $results->items());

        $this->assertSame('HIGH', $results->items()[0]->symbol);
        $this->assertSame('MID', $results->items()[1]->symbol);
        $this->assertSame('LOW', $results->items()[2]->symbol);
    }

    public function test_it_returns_etfs_sorted_by_selected_metric_ascending(): void
    {
        Carbon::setTestNow('2026-05-15');

        $bestEtf = Etf::factory()->create(['symbol' => 'BEST']);
        $worstEtf = Etf::factory()->create(['symbol' => 'WORST']);
        $middleEtf = Etf::factory()->create(['symbol' => 'MID']);

        $this->createMetric($bestEtf, ['nav_erosion_percentage' => 1.10]);
        $this->createMetric($worstEtf, ['nav_erosion_percentage' => 15.75]);
        $this->createMetric($middleEtf, ['nav_erosion_percentage' => 6.25]);

        $results = (new FilteredEtfsQuery)->getData([
            'column' => 'nav_erosion_percentage',
            'sort_direction' => 'asc',
            'scope' => 'all',
            'days' => 365,
            'per_page' => 25,
        ]);

        $this->assertCount(3, $results->items());

        $this->assertSame('BEST', $results->items()[0]->symbol);
        $this->assertSame('MID', $results->items()[1]->symbol);
        $this->assertSame('WORST', $results->items()[2]->symbol);
    }

    public function test_it_excludes_etfs_where_selected_metric_is_null(): void
    {
        Carbon::setTestNow('2026-05-15');

        $validEtf = Etf::factory()->create(['symbol' => 'VALID']);
        $nullEtf = Etf::factory()->create(['symbol' => 'NULL']);

        $this->createMetric($validEtf, ['total_return_percentage' => 4.50]);
        $this->createMetric($nullEtf, ['total_return_percentage' => null]);

        $results = (new FilteredEtfsQuery)->getData([
            'column' => 'total_return_percentage',
            'sort_direction' => 'desc',
            'scope' => 'all',
            'days' => 365,
            'per_page' => 25,
        ]);

        $this->assertCount(1, $results->items());
        $this->assertSame('VALID', $results->items()[0]->symbol);
    }

    public function test_it_filters_metrics_by_calculated_at_range(): void
    {
        Carbon::setTestNow('2026-05-15');

        $recentEtf = Etf::factory()->create(['symbol' => 'RECENT']);
        $oldEtf = Etf::factory()->create(['symbol' => 'OLD']);

        $this->createMetric($recentEtf, [
            'total_return_percentage' => 10.00,
            'calculated_at' => Carbon::now()->subDays(20),
        ]);

        $this->createMetric($oldEtf, [
            'total_return_percentage' => 99.00,
            'calculated_at' => Carbon::now()->subDays(120),
        ]);

        $results = (new FilteredEtfsQuery)->getData([
            'column' => 'total_return_percentage',
            'sort_direction' => 'desc',
            'scope' => 'all',
            'days' => 30,
            'per_page' => 25,
        ]);

        $this->assertCount(1, $results->items());
        $this->assertSame('RECENT', $results->items()[0]->symbol);
    }

    public function test_it_does_not_filter_by_date_when_days_is_null(): void
    {
        Carbon::setTestNow('2026-05-15');

        $recentEtf = Etf::factory()->create(['symbol' => 'RECENT']);
        $oldEtf = Etf::factory()->create(['symbol' => 'OLD']);

        $this->createMetric($recentEtf, [
            'aum_change_percentage' => 100,
            'calculated_at' => Carbon::now()->subDays(5),
        ]);

        $this->createMetric($oldEtf, [
            'aum_change_percentage' => 200,
            'calculated_at' => Carbon::now()->subDays(500),
        ]);

        $results = (new FilteredEtfsQuery)->getData([
            'column' => 'aum_change_percentage',
            'sort_direction' => 'desc',
            'scope' => 'all',
            'days' => null,
            'per_page' => 25,
        ]);

        $this->assertCount(2, $results->items());

        $this->assertSame('OLD', $results->items()[0]->symbol);
        $this->assertSame('RECENT', $results->items()[1]->symbol);
    }

    public function test_it_paginates_results(): void
    {
        Carbon::setTestNow('2026-05-15');

        for ($i = 1; $i <= 30; $i++) {
            $etf = Etf::factory()->create([
                'symbol' => 'ETF'.$i,
            ]);

            $this->createMetric($etf, [
                'total_return_percentage' => $i,
            ]);
        }

        $results = (new FilteredEtfsQuery)->getData([
            'column' => 'total_return_percentage',
            'sort_direction' => 'desc',
            'scope' => 'all',
            'days' => 365,
            'per_page' => 10,
        ]);

        $this->assertSame(10, $results->perPage());
        $this->assertSame(30, $results->total());
        $this->assertSame(3, $results->lastPage());
        $this->assertCount(10, $results->items());

        $this->assertSame('ETF30', $results->items()[0]->symbol);
        $this->assertSame('ETF21', $results->items()[9]->symbol);
    }

    public function test_owned_scope_without_user_id_returns_no_results(): void
    {
        Carbon::setTestNow('2026-05-15');

        $etf = Etf::factory()->create(['symbol' => 'OWND']);

        $this->createMetric($etf, [
            'total_return_percentage' => 10.00,
        ]);

        $results = (new FilteredEtfsQuery)->getData([
            'column' => 'total_return_percentage',
            'sort_direction' => 'desc',
            'scope' => 'owned',
            'days' => 365,
            'per_page' => 25,
        ]);

        $this->assertCount(0, $results->items());
        $this->assertSame(0, $results->total());
    }

    private function createMetric(Etf $etf, array $overrides = []): EtfMetric
    {
        return EtfMetric::factory()->create(array_merge([
            'etf_id' => $etf->id,
            'performance_range_type_id' => 1,

            'start_date' => Carbon::now()->subDays(30)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),

            'start_price' => 100,
            'end_price' => 110,
            'price_change' => 10,
            'price_change_percentage' => 10,

            'dividends_paid' => 0,
            'dividend_count' => 0,
            'average_dividend' => 0,

            'total_return_percentage' => 10,

            'start_nav' => 100,
            'end_nav' => 110,
            'nav_change' => 10,
            'nav_erosion_percentage' => 0,
            'nav_direction_id' => 1,

            'start_aum' => 1000000000,
            'end_aum' => 1100000000,
            'aum_change' => 100000000,
            'aum_change_percentage' => 10,
            'aum_direction_id' => 1,

            'calculated_at' => Carbon::now(),
        ], $overrides));
    }
}
