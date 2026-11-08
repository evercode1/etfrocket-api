<?php

namespace App\Services\Aum;

use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Services\AI\Extractions\AiSecurityFundDataExtractionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/*
|--------------------------------------------------------------------------
| Fund data coverage
|--------------------------------------------------------------------------
|
| An ETF is "covered" when it is active, its issuer has a fund data
| scraper (AiSecurityFundDataExtractionService::SCRAPERS) and it has an
| active FUND_DATA update schedule.
|
| Public coverage status:
|   current      has AUM data updated within STALE_AFTER_DAYS
|   stale        has AUM data, but none within STALE_AFTER_DAYS
|   pending      covered, but no AUM data collected yet
|   not_covered  not covered and no AUM data
|
| An ETF with AUM data is shown even if it is no longer covered (its data
| simply goes stale).
|
*/

class FundDataCoverage
{
    public const CURRENT = 'current';

    public const STALE = 'stale';

    public const PENDING = 'pending';

    public const NOT_COVERED = 'not_covered';

    public const STALE_AFTER_DAYS = 7;

    /**
     * Active securities that the daily scraper run will update.
     */
    public static function coveredSecurities(): Builder
    {
        return Security::query()
            ->where('securities.status_id', Status::ACTIVE)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('security_details')
                    ->whereColumn('security_details.security_id', 'securities.id')
                    ->whereIn(
                        'security_details.etf_issuer_id',
                        AiSecurityFundDataExtractionService::supportedIssuerIds()
                    );
            })
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('security_update_schedules')
                    ->whereColumn('security_update_schedules.security_id', 'securities.id')
                    ->where('security_update_schedules.security_update_type_id', SecurityUpdateType::FUND_DATA)
                    ->where('security_update_schedules.status_id', Status::ACTIVE);
            });
    }

    public static function isCovered(Security $security): bool
    {
        return self::coveredSecurities()
            ->whereKey($security->id)
            ->exists();
    }

    /**
     * @return array<int, string> security_id => latest aum_date (Y-m-d)
     */
    public static function latestAumDates(): array
    {
        return SecurityAumHistory::query()
            ->selectRaw('security_id, MAX(aum_date) AS latest_date')
            ->groupBy('security_id')
            ->pluck('latest_date', 'security_id')
            ->map(fn ($date) => (string) $date)
            ->all();
    }

    public static function isStale(?string $latestDate): bool
    {
        if (! $latestDate) {

            return false;
        }

        return CarbonImmutable::parse($latestDate)
            ->lt(CarbonImmutable::today()->subDays(self::STALE_AFTER_DAYS));
    }

    public static function status(bool $covered, ?string $latestDate): string
    {
        if ($latestDate) {

            return self::isStale($latestDate) ? self::STALE : self::CURRENT;
        }

        return $covered ? self::PENDING : self::NOT_COVERED;
    }
}
