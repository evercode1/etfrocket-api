<?php

namespace App\Services\Crons;

use App\Models\Security;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| Fund data schedule provisioner
|--------------------------------------------------------------------------
|
| Creates an active FUND_DATA schedule for securities that have none (see
| FundDataCoverage::unscheduledSecurities()), so the hourly run starts
| scraping them. Each new schedule gets the currently least-used
| run_hour to spread scraping load across the day.
|
| run_day is stored for the admin form but ignored by the daily fund data
| run (RunScheduledUpdatesHandler matches run_hour only).
|
*/

class FundDataScheduleProvisioner
{
    /**
     * @param  Collection<int, Security>  $securities  securities without a FUND_DATA schedule
     * @return Collection<int, SecurityUpdateSchedule> the schedules created
     */
    public function provision(Collection $securities): Collection
    {
        $hourLoad = SecurityUpdateSchedule::query()
            ->where('security_update_type_id', SecurityUpdateType::FUND_DATA)
            ->where('status_id', Status::ACTIVE)
            ->selectRaw('run_hour, COUNT(*) AS total')
            ->groupBy('run_hour')
            ->pluck('total', 'run_hour')
            ->all();

        $load = array_replace(array_fill(0, 24, 0), $hourLoad);

        return $securities->map(function (Security $security) use (&$load) {

            $runHour = array_keys($load, min($load), true)[0];

            $load[$runHour]++;

            return SecurityUpdateSchedule::create([
                'security_id' => $security->id,
                'security_update_type_id' => SecurityUpdateType::FUND_DATA,
                'run_day' => 1,
                'run_hour' => $runHour,
                'status_id' => Status::ACTIVE,
            ]);
        })->values();
    }
}
