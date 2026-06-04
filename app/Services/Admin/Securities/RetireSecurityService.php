<?php

namespace App\Services\Admin\Securities;

use App\Models\Security;
use App\Models\SecurityUpdateSchedule;
use App\Models\Status;
use Illuminate\Support\Facades\DB;

class RetireSecurityService
{
    public function retire(
        int $securityId
    ): Security {

        return DB::transaction(

            function () use (

                $securityId

            ) {

                $security =
                    Security::findOrFail(
                        $securityId
                    );

                $security->update([

                    'status_id' => Status::RETIRED,

                ]);

                SecurityUpdateSchedule::where(

                    'security_id',

                    $security->id

                )->update([

                    'status_id' => Status::RETIRED,

                ]);

                return $security->fresh([
                    'updateSchedules',
                ]);

            }

        );
    }
}
