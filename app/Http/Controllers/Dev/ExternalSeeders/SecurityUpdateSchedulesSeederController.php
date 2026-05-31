<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\Security;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use Illuminate\Support\Facades\DB;

class SecurityUpdateSchedulesSeederController extends Controller
{
    public function run(): void
    {
        DB::table('security_update_schedules')
            ->truncate();

        $now = now();

        $records = [];

        $securities =
            Security::orderBy('id')->get();

        foreach (
            $securities as $index => $security
        ) {

            foreach ([
                SecurityUpdateType::DIVIDEND,
                SecurityUpdateType::AUM,
                SecurityUpdateType::NAV,
            ] as $updateTypeId) {

                $slot =
                    ($index * 3)
                    + ($updateTypeId - 1);

                $records[] = [

                    'security_id' =>
                        $security->id,

                    'security_update_type_id' =>
                        $updateTypeId,

                    'run_day' =>
                        ($slot % 7) + 1,

                    'run_hour' =>
                        intdiv($slot, 7) % 24,

                    'status_id' =>
                        Status::ACTIVE,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ];
            }
        }

        SecurityUpdateSchedule::insert(
            $records
        );
    }
}
