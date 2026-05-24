<?php

namespace App\Services\Crons\Handlers;

use App\Models\SignalType;
use App\Services\AI\AiSignals\GenerateAiSignalService;

class GenerateAiSignalsHandler
{
    public function __construct(

        private GenerateAiSignalService
        $generateAiSignalService

    ) {}

    // we ignore payload, but we need it for dynamic method calling in CronService

    public function handleGenerateAiSignals(

        array $payload = []

    ): array {

        try {

            $signalTypes = [

                SignalType::MARKET_SNAPSHOT,

                SignalType::MARKET_CONDITIONS,

                SignalType::MARKET_EVENTS,

            ];

            foreach (
                $signalTypes
                as $signalType
            ) {

                $this->generateAiSignalService
                    ->generate(
                        $signalType
                    );
            }

            return [

                'success' => 1,

                'cron_fail_details' => null,

            ];
        } catch (\Exception $e) {

            return [

                'success' => 0,

                'cron_fail_details' =>

                $this->errorMessage() .

                    $e->getMessage(),

            ];
        }
    }

    public function errorMessage(): string
    {
        return
            'AI signal generation failed. ';
    }
}
