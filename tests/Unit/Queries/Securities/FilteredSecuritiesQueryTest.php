<?php

namespace Tests\Unit\Queries\Securities;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityMetric;
use App\Queries\SEcurities\FilteredSecuritiesQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FilteredSecuritiesQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_securities_sorted_by_selected_metric_descending(): void
    {
        Carbon::setTestNow('2026-05-15');

        $lowSecurity = Security::create(['symbol' => 'LOW']);
        $highSecurity = Security::create(['symbol' => 'HIGH']);
        $middleSecurity = Security::create(['symbol' => 'MID']);

        SecurityDetail::factory()->create(['security_id' => $lowSecurity->id]);
        SecurityDetail::factory()->create(['security_id' => $highSecurity->id]);
        SecurityDetail::factory()->create(['security_id' => $middleSecurity->id]);

        $this->createMetric($lowSecurity, ['total_return_percentage' => 5.25]);
        $this->createMetric($highSecurity, ['total_return_percentage' => 22.75]);
        $this->createMetric($middleSecurity, ['total_return_percentage' => 12.50]);

        $results = (new FilteredSecuritiesQuery)->getData([
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

    public function test_it_returns_securities_sorted_by_selected_metric_ascending(): void
    {
        Carbon::setTestNow('2026-05-15');

        $bestSecurity = Security::create(['symbol' => 'BEST']);
        $worstSecurity = Security::create(['symbol' => 'WORST']);
        $middleSecurity = Security::create(['symbol' => 'MID']);

        SecurityDetail::factory()->create(['security_id' => $bestSecurity->id]);
        SecurityDetail::factory()->create(['security_id' => $worstSecurity->id]);
        SecurityDetail::factory()->create(['security_id' => $middleSecurity->id]);

        $this->createMetric($bestSecurity, ['nav_erosion_percentage' => 1.10]);
        $this->createMetric($worstSecurity, ['nav_erosion_percentage' => 15.75]);
        $this->createMetric($middleSecurity, ['nav_erosion_percentage' => 6.25]);

        $results = (new FilteredSecuritiesQuery)->getData([
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

    public function test_it_excludes_securities_where_selected_metric_is_null(): void
    {
        Carbon::setTestNow('2026-05-15');

        $validSecurity = Security::create(['symbol' => 'VALID']);
        $nullSecurity = Security::create(['symbol' => 'NULL']);

        SecurityDetail::factory()->create(['security_id' => $validSecurity->id]);
        SecurityDetail::factory()->create(['security_id' => $nullSecurity->id]);

        $this->createMetric($validSecurity, ['total_return_percentage' => 4.50]);
        $this->createMetric($nullSecurity, ['total_return_percentage' => null]);

        $results = (new FilteredSecuritiesQuery)->getData([
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

        $recentSecurity = Security::create(['symbol' => 'RECENT']);
        $oldSecurity = Security::create(['symbol' => 'OLD']);

        SecurityDetail::factory()->create(['security_id' => $recentSecurity->id]);
        SecurityDetail::factory()->create(['security_id' => $oldSecurity->id]);

        $this->createMetric($recentSecurity, [
            'total_return_percentage' => 10.00,
            'calculated_at' => Carbon::now()->subDays(20),
        ]);

        $this->createMetric($oldSecurity, [
            'total_return_percentage' => 99.00,
            'calculated_at' => Carbon::now()->subDays(120),
        ]);

        $results = (new FilteredSecuritiesQuery)->getData([
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

        $recentSecurity = Security::create(['symbol' => 'RECENT']);
        $oldSecurity = Security::create(['symbol' => 'OLD']);

        SecurityDetail::factory()->create(['security_id' => $recentSecurity->id]);
        SecurityDetail::factory()->create(['security_id' => $oldSecurity->id]);

        $this->createMetric($recentSecurity, [
            'aum_change_percentage' => 100,
            'calculated_at' => Carbon::now()->subDays(5),
        ]);

        $this->createMetric($oldSecurity, [
            'aum_change_percentage' => 200,
            'calculated_at' => Carbon::now()->subDays(500),
        ]);

        $results = (new FilteredSecuritiesQuery)->getData([
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
            $security = Security::create(['symbol' => 'SEC'.$i]);
            SecurityDetail::factory()->create(['security_id' => $security->id]);

            $this->createMetric($security, [
                'total_return_percentage' => $i,
            ]);
        }

        $results = (new FilteredSecuritiesQuery)->getData([
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

        $this->assertSame('SEC30', $results->items()[0]->symbol);
        $this->assertSame('SEC21', $results->items()[9]->symbol);
    }

    public function test_owned_scope_without_user_id_returns_no_results(): void
    {
        Carbon::setTestNow('2026-05-15');

        $security = Security::create(['symbol' => 'OWND']);
        SecurityDetail::factory()->create(['security_id' => $security->id]);

        $this->createMetric($security, [
            'total_return_percentage' => 10.00,
        ]);

        $results = (new FilteredSecuritiesQuery)->getData([
            'column' => 'total_return_percentage',
            'sort_direction' => 'desc',
            'scope' => 'owned',
            'days' => 365,
            'per_page' => 25,
        ]);

        $this->assertCount(0, $results->items());
        $this->assertSame(0, $results->total());
    }

    private function createMetric(Security $security, array $overrides = []): SecurityMetric
    {
        return SecurityMetric::factory()->create(array_merge([
            'security_id' => $security->id,
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
