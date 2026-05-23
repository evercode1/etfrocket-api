<?php

namespace App\Console\Commands;

use App\Models\SignalType;
use Illuminate\Console\Command;
use App\Services\AI\AiSignals\GenerateAiSignalService;

class GenerateAiSignalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Examples:
     *
     * php artisan ai:generate-signals
     * php artisan ai:generate-signals --type=1
     */
    protected $signature =
    'ai:generate-signals
        {--type=}';

    /**
     * The console command description.
     */
    protected $description =
    'Generate AI market signals';

    public function __construct(

        private GenerateAiSignalService
        $generateAiSignalService

    ) {

        parent::__construct();
    }

    public function handle(): int
    {
        $type =
            $this->option('type');

        if ($type) {

            $this->generateSignal(
                (int) $type
            );

            return self::SUCCESS;
        }

        $signalTypes = [

            SignalType::MARKET_SNAPSHOT,

            SignalType::MARKET_CONDITIONS,

            SignalType::MARKET_EVENTS,

        ];

        foreach (
            $signalTypes
            as $signalType
        ) {

            $this->generateSignal(
                $signalType
            );
        }

        $this->info(
            'AI signals generated successfully.'
        );

        return self::SUCCESS;
    }

    private function generateSignal(
        int $signalTypeId
    ): void {

        $signal =
            $this->generateAiSignalService
            ->generate(
                $signalTypeId
            );

        $this->info(

            sprintf(

                '[%s] Generated: %s',

                $signal->id,

                $signal->title

            )

        );
    }
}
