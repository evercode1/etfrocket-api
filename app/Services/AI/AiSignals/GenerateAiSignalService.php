<?php

namespace App\Services\AI\AiSignals;

use App\Models\AiMarketSignal;
use App\Models\SignalType;
use App\Services\AI\AiSignals\Payloads\EtfWatchlistPayloadService;
use App\Services\AI\AiSignals\Payloads\MarketConditionsPayloadService;
use App\Services\AI\AiSignals\Payloads\MarketSnapshotPayloadService;
use App\Services\AI\MarketAnalytics\MarketMoodService;
use Exception;

class GenerateAiSignalService
{
    public function __construct(

        private GenerateAiSignalContentService $generateAiSignalContentService

    ) {}

    public function generate(
        int $signal_type_id
    ): AiMarketSignal {

        $template =
            $this->getTemplate(
                $signal_type_id
            );

        $payload =
            $this->getPayload(
                $signal_type_id
            );

        $generatedMarkdown =
            $this->generateAiSignalContentService
                ->generate(

                    $signal_type_id,

                    $payload

                );

        $moodData = $this->getMarketMood();

        $signal =
            AiMarketSignal::create([

                'signal_type_id' => $signal_type_id,

                'title' => $this->getTitle(
                    $signal_type_id
                ),

                'subtitle' => $this->getSubtitle(
                    $signal_type_id
                ),

                'market_mood' => $moodData['market_mood'],

                'confidence_score' => $moodData['confidence_score'],

                'markdown_content' => $generatedMarkdown,

                'payload_json' => [

                    'template_used' => basename($template),

                    'market_status' => app(

                        IsMarketOpenService::class

                    )->isOpen()

                        ? 'OPEN'

                        : 'CLOSED',

                    'signal_payload' => $payload,

                ],

                'generated_at' => now(),

                'expires_at' => now()->addDay(),

                'is_active' => true,

                'ai_model' => config(
                    'services.openai.model',
                    'gpt-4.1-mini'
                ),

            ]);

        return $signal;
    }

    private function getTemplate(
        int $signal_type_id
    ): string {

        return match ($signal_type_id) {

            SignalType::MARKET_SNAPSHOT => app_path(
                'Services/AI/AiSignals/Templates/market_snapshot.md'
            ),

            SignalType::MARKET_CONDITIONS => app_path(
                'Services/AI/AiSignals/Templates/market_conditions.md'
            ),

            SignalType::MARKET_EVENTS => app_path(
                'Services/AI/AiSignals/Templates/market_events.md'
            ),

            SignalType::ETF_WATCHLIST => app_path(
                'Services/AI/AiSignals/Templates/etf_watchlist.md'
            ),

            default => throw new Exception(
                'Invalid signal type.'
            ),
        };
    }

    private function getTitle(
        int $signal_type_id
    ): string {

        return match ($signal_type_id) {

            SignalType::MARKET_SNAPSHOT => 'AI Market Snapshot',

            SignalType::MARKET_CONDITIONS => 'AI Market Conditions',

            SignalType::MARKET_EVENTS => 'AI Market Events',

            SignalType::ETF_WATCHLIST => 'AI ETF Watchlist',

            default => 'AI Signal',
        };
    }

    private function getSubtitle(
        int $signal_type_id
    ): string {

        return match ($signal_type_id) {

            SignalType::MARKET_SNAPSHOT => 'Daily AI-generated overview of market sentiment and macro positioning.',

            SignalType::MARKET_CONDITIONS => 'AI interpretation of volatility, momentum, and market behavior.',

            SignalType::MARKET_EVENTS => 'Upcoming catalysts and macro events impacting financial markets.',

            SignalType::ETF_WATCHLIST => 'AI-generated ETF rankings highlighting performance, momentum, investor flows, and fund quality.',

            default => 'AI-generated signal.',
        };
    }

    private function getMarketMood(): array
    {

        return app(

            MarketMoodService::class

        )->determine();
    }

    private function getPayload(
        int $signal_type_id
    ): array {
        return match ($signal_type_id) {

            SignalType::MARKET_SNAPSHOT => app(
                MarketSnapshotPayloadService::class
            )->getData(),

            SignalType::MARKET_CONDITIONS => app(
                MarketConditionsPayloadService::class
            )->getData(),

            SignalType::MARKET_EVENTS => [],

            SignalType::ETF_WATCHLIST => app(

                EtfWatchlistPayloadService::class

            )->getData(),

            default => [],
        };
    }
}
