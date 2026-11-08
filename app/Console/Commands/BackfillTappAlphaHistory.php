<?php

namespace App\Console\Commands;

use App\Http\Controllers\Public\Aum\AumHistoryController;
use App\Http\Controllers\Public\Aum\AumLeaderboardController;
use App\Http\Controllers\Public\Aum\EtfSearchIndexController;
use App\Models\DataSource;
use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\Status;
use App\Services\Scrapers\TappAlphaScraperService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/*
|--------------------------------------------------------------------------
| Backfill TappAlpha AUM / NAV history
|--------------------------------------------------------------------------
|
| TappAlpha's public fund data API (see TappAlphaScraperService) returns
| each fund's daily history since launch in "chart":
|
|   { "ticker": "TDAQ", "as_of_date": "2025-09-03", "nav": 24.76, "net_assets": 247624 }
|
| This command copies that history into security_aum_histories and
| security_nav_histories for active TappAlpha securities.
|
| Safe to run in production and to re-run: it only inserts dates that are
| missing (insertOrIgnore on the security + date unique keys) and never
| overwrites existing rows, including data from the daily scraper.
|
*/

class BackfillTappAlphaHistory extends Command
{
    protected $signature =

        'securities:backfill-tappalpha-history

        {--symbol= : Only backfill this TappAlpha symbol}

        {--dry-run : Report what would be inserted without writing}';

    protected $description =

        'Backfill AUM and NAV history for TappAlpha ETFs from their public fund data API (insert-only).';

    private const CHUNK_SIZE = 500;

    public function handle(TappAlphaScraperService $tappAlpha): int
    {
        $securities = $this->securities();

        if ($securities->isEmpty()) {

            $this->error(
                $this->option('symbol')
                    ? 'No active TappAlpha security found for '.strtoupper($this->option('symbol')).'.'
                    : 'No active TappAlpha securities found.'
            );

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {

            $this->warn('Dry run: nothing will be written.');
        }

        $rows = [];

        $failed = 0;

        foreach ($securities as $security) {

            try {

                $payload = $tappAlpha->fetchFundData($security->symbol);

                [$aumRows, $navRows, $skipped] = $this->historyRows($security, $payload['chart'] ?? []);

                $aumInserted = $this->insert('security_aum_histories', 'aum_date', $security, $aumRows, $dryRun);

                $navInserted = $this->insert('security_nav_histories', 'nav_date', $security, $navRows, $dryRun);

                $rows[] = [
                    $security->symbol,
                    count($aumRows),
                    $aumInserted,
                    $navInserted,
                    $skipped,
                    $aumRows ? $aumRows[0]['aum_date'].' to '.end($aumRows)['aum_date'] : '-',
                ];

                if (! $dryRun && $aumInserted + $navInserted > 0) {

                    Cache::forget(AumHistoryController::cacheKey($security->symbol));
                }
            } catch (Throwable $e) {

                $failed++;

                $rows[] = [$security->symbol, '-', '-', '-', '-', 'ERROR: '.$e->getMessage()];
            }
        }

        $this->table(
            ['Symbol', 'API days', $dryRun ? 'AUM to add' : 'AUM added', $dryRun ? 'NAV to add' : 'NAV added', 'Invalid rows', 'Range / error'],
            $rows
        );

        if (! $dryRun) {

            Cache::forget(AumLeaderboardController::CACHE_KEY);
            Cache::forget(EtfSearchIndexController::CACHE_KEY);
        }

        return $failed === $securities->count() ? self::FAILURE : self::SUCCESS;
    }

    private function securities()
    {
        return Security::query()
            ->with('detail')
            ->where('status_id', Status::ACTIVE)
            ->whereHas('detail', fn ($query) => $query->where('etf_issuer_id', EtfIssuer::TAPPALPHA))
            ->when(
                $this->option('symbol'),
                fn ($query, $symbol) => $query->where('symbol', strtoupper($symbol))
            )
            ->orderBy('symbol')
            ->get();
    }

    /**
     * Valid chart points as AUM and NAV rows, oldest first.
     *
     * @return array{0: array, 1: array, 2: int}
     */
    private function historyRows(Security $security, array $chart): array
    {
        $today = CarbonImmutable::today();

        $now = now();

        $aumRows = [];

        $navRows = [];

        $skipped = 0;

        foreach ($chart as $point) {

            $date = $this->validDate($point['as_of_date'] ?? null, $today);

            $nav = is_numeric($point['nav'] ?? null) ? (float) $point['nav'] : 0;

            $netAssets = is_numeric($point['net_assets'] ?? null) ? (float) $point['net_assets'] : 0;

            if (
                strtoupper($point['ticker'] ?? $security->symbol) !== strtoupper($security->symbol)
                || ! $date
                || $nav <= 0
                || $netAssets <= 0
            ) {

                $skipped++;

                continue;
            }

            $aumRows[$date] = [
                'security_id' => $security->id,
                'aum_date' => $date,
                'assets_under_management' => (int) round($netAssets),
                'data_source_id' => DataSource::WEB_SCRAPER,
                'retrieved_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $navRows[$date] = [
                'security_id' => $security->id,
                'nav_date' => $date,
                'nav_per_share' => round($nav, 4),
                'data_source_id' => DataSource::WEB_SCRAPER,
                'retrieved_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ksort($aumRows);

        ksort($navRows);

        return [array_values($aumRows), array_values($navRows), $skipped];
    }

    private function validDate(mixed $value, CarbonImmutable $today): ?string
    {
        if (! is_string($value)) {

            return null;
        }

        try {

            $date = CarbonImmutable::parse($value)->startOfDay();
        } catch (Throwable) {

            return null;
        }

        return $date->gt($today) ? null : $date->toDateString();
    }

    /**
     * Insert-only. In a dry run, returns how many dates are missing.
     */
    private function insert(string $table, string $dateColumn, Security $security, array $rows, bool $dryRun): int
    {
        if (empty($rows)) {

            return 0;
        }

        if ($dryRun) {

            $existing = DB::table($table)
                ->where('security_id', $security->id)
                ->whereIn($dateColumn, array_column($rows, $dateColumn))
                ->count();

            return count($rows) - $existing;
        }

        $inserted = 0;

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {

            $inserted += DB::table($table)->insertOrIgnore($chunk);
        }

        return $inserted;
    }
}
