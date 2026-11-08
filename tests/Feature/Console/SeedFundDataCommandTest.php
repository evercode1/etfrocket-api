<?php

namespace Tests\Feature\Console;

use App\Models\DataSource;
use App\Models\DistributionFrequency;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityDetail;
use App\Models\SecurityNavHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeedFundDataCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-01 12:00:00');

        $this->truncateTables();
    }

    protected function tearDown(): void
    {
        $this->truncateTables();

        Carbon::setTestNow();

        parent::tearDown();
    }

    private function truncateTables(): void
    {
        DB::table('security_aum_histories')->truncate();
        DB::table('security_nav_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
    }

    private function makeSecurity(string $symbol): Security
    {
        $security = Security::factory()->create([
            'symbol' => $symbol,
        ]);

        // SecurityFactory creates the detail row via afterCreating.
        SecurityDetail::where('security_id', $security->id)->update([
            'distribution_frequency_id' => DistributionFrequency::WEEKLY,
        ]);

        return $security;
    }

    public function test_it_generates_matching_aum_and_nav_rows_on_weekdays(): void
    {
        $security = $this->makeSecurity('TSTA');

        $this->artisan('dev:seed-fund-data', ['--days' => 30])
            ->assertSuccessful();

        $aumRows = SecurityAumHistory::where('security_id', $security->id)->get();
        $navRows = SecurityNavHistory::where('security_id', $security->id)->get()->keyBy(
            fn ($row) => $row->nav_date->toDateString()
        );

        $this->assertNotEmpty($aumRows);
        $this->assertCount($aumRows->count(), $navRows);

        foreach ($aumRows as $aum) {

            $this->assertFalse($aum->aum_date->isWeekend());
            $this->assertEquals(DataSource::MANUAL_ENTRY, $aum->data_source_id);
            $this->assertGreaterThan(0, $aum->assets_under_management);

            $nav = $navRows->get($aum->aum_date->toDateString());

            $this->assertNotNull($nav);
            $this->assertGreaterThan(0, (float) $nav->nav_per_share);
        }
    }

    public function test_it_is_deterministic_and_does_not_duplicate_rows(): void
    {
        $security = $this->makeSecurity('TSTB');

        $this->artisan('dev:seed-fund-data', ['--days' => 30])->assertSuccessful();

        $first = SecurityAumHistory::where('security_id', $security->id)
            ->orderBy('aum_date')
            ->pluck('assets_under_management')
            ->all();

        $this->artisan('dev:seed-fund-data', ['--days' => 30])->assertSuccessful();

        $this->assertSame(
            count($first),
            SecurityAumHistory::where('security_id', $security->id)->count()
        );

        $this->artisan('dev:seed-fund-data', ['--days' => 30, '--fresh' => true])->assertSuccessful();

        $this->assertSame(
            $first,
            SecurityAumHistory::where('security_id', $security->id)
                ->orderBy('aum_date')
                ->pluck('assets_under_management')
                ->all()
        );
    }

    public function test_it_does_not_overwrite_existing_rows(): void
    {
        $security = $this->makeSecurity('TSTC');

        SecurityAumHistory::create([
            'security_id' => $security->id,
            'aum_date' => '2026-04-30',
            'assets_under_management' => 123,
            'data_source_id' => DataSource::WEB_SCRAPER,
        ]);

        $this->artisan('dev:seed-fund-data', ['--days' => 30])->assertSuccessful();

        $this->assertDatabaseHas('security_aum_histories', [
            'security_id' => $security->id,
            'aum_date' => '2026-04-30',
            'assets_under_management' => 123,
            'data_source_id' => DataSource::WEB_SCRAPER,
        ]);
    }

    public function test_it_can_target_a_single_symbol(): void
    {
        $target = $this->makeSecurity('TSTD');
        $other = $this->makeSecurity('TSTE');

        $this->artisan('dev:seed-fund-data', ['--days' => 30, '--symbol' => 'tstd'])
            ->assertSuccessful();

        $this->assertGreaterThan(0, SecurityAumHistory::where('security_id', $target->id)->count());
        $this->assertSame(0, SecurityAumHistory::where('security_id', $other->id)->count());
    }

    public function test_it_refuses_to_run_in_production(): void
    {
        $this->makeSecurity('TSTF');

        $this->app['env'] = 'production';

        $this->artisan('dev:seed-fund-data', ['--days' => 30])
            ->assertFailed();

        $this->assertSame(0, SecurityAumHistory::count());
    }
}
