<?php

namespace App\Services\Admin\Securities;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityUpdateSchedule;
use Illuminate\Support\Facades\DB;

class CreateSecurityService
{
    public function store(
        array $data
    ): Security {

        return DB::transaction(

            function () use ($data) {

                /*
                |--------------------------------------------------------------------------
                | Security
                |--------------------------------------------------------------------------
                */

                $security =
                    Security::create([

                        'symbol' => strtoupper(
                            $data['symbol']
                        ),

                        'security_type_id' => $data['security_type_id'],

                        'status_id' => $data['status_id'],

                    ]);

                /*
                |--------------------------------------------------------------------------
                | Security Detail
                |--------------------------------------------------------------------------
                */

                SecurityDetail::create([

                    'security_id' => $security->id,

                    'security_name' => $data['security_name'],

                    'etf_issuer_id' => $data['etf_issuer_id'],

                    'etf_strategy_type_id' => $data['etf_strategy_type_id'],

                    'distribution_frequency_id' => $data['distribution_frequency_id'],

                    'expense_ratio' => $data['expense_ratio'],

                    'website_url' => $data['website_url'],

                    'notes' => $data['notes'],

                ]);

                /*
                |--------------------------------------------------------------------------
                | Update Schedules
                |--------------------------------------------------------------------------
                */

                foreach (

                    $data['schedules'] ?? [] as $schedule

                ) {

                    SecurityUpdateSchedule::create([

                        'security_id' => $security->id,

                        'security_update_type_id' => $schedule['security_update_type_id'],

                        'run_day' => $schedule['run_day'],

                        'run_hour' => $schedule['run_hour'],

                        'status_id' => $schedule['status_id'],

                    ]);
                }

                return $security->fresh();

            }

        );
    }
}
