<?php

namespace App\Queries\AiSignals;

use App\Models\AiMarketSignal;

class GetLatestAiSignalsQuery
{
    public function getData(
        array $signal_type_ids = []
    ) {

        $query =
            AiMarketSignal::query()
                ->where(
                    'is_active',
                    true
                )
                ->with(
                    'signalType'
                )
                ->orderByDesc(
                    'generated_at'
                );

        if (
            ! empty($signal_type_ids)
        ) {

            $query->whereIn(

                'signal_type_id',

                $signal_type_ids

            );
        }

        $signals =
            $query->get()
                ->unique(
                    'signal_type_id'
                )
                ->values();

        return $signals;
    }
}
