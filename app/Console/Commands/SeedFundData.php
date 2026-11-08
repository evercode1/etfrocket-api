<?php

namespace App\Console\Commands;

use App\Models\DataSource;
use App\Models\DistributionFrequency;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityNavHistory;
use App\Models\Status;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/*
|--------------------------------------------------------------------------
| Synthetic fund data for local development
|--------------------------------------------------------------------------
|
| Generates realistic-looking daily AUM + NAV history for existing
| securities so the AUM features can be built without production data.
|
| - Refuses to run in production.
| - Deterministic per symbol: the same symbol always gets the same series.
| - Rows are tagged DataSource::MANUAL_ENTRY so they are distinguishable
|   from scraped data (DataSource::WEB_SCRAPER).
| - Never overwrites existing rows (insertOrIgnore on the unique
|   security + date keys) unless --fresh truncates the tables first.
|
*/

class SeedFundData extends Command
{
    protected $signature =

        'dev:seed-fund-data

        {--days=365 : Calendar days of history to generate, ending today}

        {--symbol= : Only generate data for this symbol}

        {--fresh : Truncate security_aum_histories and security_nav_histories first}';

    protected $description =

        'Generate synthetic AUM and NAV history for local development (refuses to run in production).';

    private const CHUNK_SIZE = 1000;

    public function handle(): int
    {
        if (app()->environment('production')) {

            $this->error('dev:seed-fund-data cannot run in production.');

            return self::FAILURE;
        }

        $days = (int) $this->option('days');

        if ($days < 1) {

            $this->error('--days must be at least 1.');

            return self::FAILURE;
        }

        $securities = $this->securities();

        if ($securities->isEmpty()) {

            $this->error('No active securities with details found.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {

            SecurityAumHistory::truncate();

            SecurityNavHistory::truncate();

            $this->info('Truncated security_aum_histories and security_nav_histories.');
        }

        $tradingDays = $this->tradingDays($days);

        $aumInserted = 0;

        $navInserted = 0;

        $this->withProgressBar(
            $securities,
            function (Security $security) use ($tradingDays, &$aumInserted, &$navInserted) {

                [$aumRows, $navRows] = $this->generateSeries(
                    $security,
                    $tradingDays
                );

                $aumInserted += $this->insert(
                    'security_aum_histories',
                    $aumRows
                );

                $navInserted += $this->insert(
                    'security_nav_histories',
                    $navRows
                );
            }
        );

        $this->newLine(2);

        $this->info(
            "Seeded {$securities->count()} securities: ".
            "{$aumInserted} AUM rows, {$navInserted} NAV rows ".
            "({$tradingDays[0]->toDateString()} to ".
            end($tradingDays)->toDateString().').'
        );

        $this->line('Next: php artisan securities:calculate-metrics');

        return self::SUCCESS;
    }

    private function securities()
    {
        $query =

            Security::query()

                ->with('detail')

                ->where(
                    'status_id',
                    Status::ACTIVE
                )

                ->whereHas('detail')

                ->orderBy('symbol');

        if ($symbol = $this->option('symbol')) {

            $query->where(
                'symbol',
                strtoupper($symbol)
            );
        }

        return $query->get();
    }

    /**
     * @return CarbonImmutable[]
     */
    private function tradingDays(int $days): array
    {
        $end = CarbonImmutable::today();

        $date = $end->subDays($days - 1);

        $tradingDays = [];

        while ($date->lte($end)) {

            if (! $date->isWeekend()) {

                $tradingDays[] = $date;
            }

            $date = $date->addDay();
        }

        return $tradingDays;
    }

    /**
     * @param  CarbonImmutable[]  $tradingDays
     * @return array{0: array, 1: array}
     */
    private function generateSeries(
        Security $security,
        array $tradingDays
    ): array {

        $random = new Randomizer(
            new Mt19937(crc32($security->symbol))
        );

        /*
        |--------------------------------------------------------------------------
        | Per-fund profile
        |--------------------------------------------------------------------------
        */

        // Some funds launch partway through the window, so "new fund" cases exist.
        $launchIndex =
            $random->getFloat(0, 1) < 0.25

                ? (int) floor(count($tradingDays) * $random->getFloat(0.2, 0.8))

                : 0;

        $nav = $random->getFloat(10, 60);

        // Total-return drift and volatility (daily).
        $dailyDrift = $random->getFloat(-0.05, 0.20) / 252;

        $dailyVolatility = $random->getFloat(0.008, 0.03);

        // Annual distribution yield, paid out as NAV drops on distribution days.
        $annualYield = $random->getFloat(0.15, 0.70);

        $distributionsPerYear = $this->distributionsPerYear(
            $security->detail->distribution_frequency_id
        );

        $shares =
            $launchIndex > 0

                ? $random->getInt(1, 3) * 100_000

                : $random->getInt(5, 200) * 100_000;

        // Long-run share growth trend (log space): shrinking to fast-growing.
        // New launches skew toward strong inflows.
        $dailyShareTrend =
            $launchIndex > 0

                ? $random->getFloat(0.5, 3.0) / 252

                : $random->getFloat(-0.8, 1.6) / 252;

        $now = Carbon::now();

        $aumRows = [];

        $navRows = [];

        foreach ($tradingDays as $index => $date) {

            if ($index < $launchIndex) {

                continue;
            }

            if ($index > $launchIndex) {

                /*
                |--------------------------------------------------------------------------
                | NAV: random walk + distribution drops
                |--------------------------------------------------------------------------
                */

                $nav *= 1 + $dailyDrift + $dailyVolatility * $this->gaussian($random);

                if ($this->isDistributionDay($date, $distributionsPerYear)) {

                    $nav *= 1 - ($annualYield / $distributionsPerYear);
                }

                $nav = max($nav, 1.0);

                /*
                |--------------------------------------------------------------------------
                | Shares outstanding: creations / redemptions in 10k-share units
                |--------------------------------------------------------------------------
                */

                if ($random->getFloat(0, 1) < 0.35) {

                    $flowPercentage =

                        $dailyShareTrend / 0.35

                        + 0.02 * $this->gaussian($random);

                    $units = (int) round(
                        ($shares * $flowPercentage) / 10_000
                    );

                    $shares = max(
                        50_000,
                        $shares + $units * 10_000
                    );
                }
            }

            $roundedNav = round($nav, 4);

            $aumRows[] = [
                'security_id' => $security->id,
                'aum_date' => $date->toDateString(),
                'assets_under_management' => (int) round($roundedNav * $shares),
                'data_source_id' => DataSource::MANUAL_ENTRY,
                'retrieved_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $navRows[] = [
                'security_id' => $security->id,
                'nav_date' => $date->toDateString(),
                'nav_per_share' => $roundedNav,
                'data_source_id' => DataSource::MANUAL_ENTRY,
                'retrieved_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return [$aumRows, $navRows];
    }

    private function distributionsPerYear(?int $distributionFrequencyId): int
    {
        return match ($distributionFrequencyId) {

            DistributionFrequency::WEEKLY => 52,

            DistributionFrequency::BI_WEEKLY => 26,

            DistributionFrequency::QUARTERLY => 4,

            DistributionFrequency::SEMI_ANNUAL => 2,

            DistributionFrequency::ANNUAL => 1,

            default => 12,
        };
    }

    private function isDistributionDay(
        CarbonImmutable $date,
        int $distributionsPerYear
    ): bool {

        return match (true) {

            $distributionsPerYear >= 52 => $date->isThursday(),

            $distributionsPerYear >= 26 => $date->isThursday() && $date->weekOfYear % 2 === 0,

            $distributionsPerYear >= 12 => $this->isLastTradingDayOfMonth($date),

            default => $this->isLastTradingDayOfMonth($date)
                && $date->month % (int) (12 / $distributionsPerYear) === 0,
        };
    }

    private function isLastTradingDayOfMonth(CarbonImmutable $date): bool
    {
        $next = $date->addDay();

        while ($next->isWeekend()) {

            $next = $next->addDay();
        }

        return $next->month !== $date->month;
    }

    private function gaussian(Randomizer $random): float
    {
        // Box-Muller transform.
        $u1 = max($random->getFloat(0, 1), PHP_FLOAT_MIN);

        $u2 = $random->getFloat(0, 1);

        return sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
    }

    private function insert(string $table, array $rows): int
    {
        $inserted = 0;

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {

            $inserted += DB::table($table)->insertOrIgnore($chunk);
        }

        return $inserted;
    }
}
