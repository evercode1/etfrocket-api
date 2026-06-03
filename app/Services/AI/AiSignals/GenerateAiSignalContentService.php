<?php

namespace App\Services\AI\AiSignals;

use App\Models\SignalType;
use Exception;
use Illuminate\Support\Facades\Http;

class GenerateAiSignalContentService
{
    public function __construct(

        private IsMarketOpenService $isMarketOpenService

    ) {}

    public function generate(
        int $signal_type_id,
        array $payload = []
    ): string {

        $template =
            $this->getTemplateContent(
                $signal_type_id
            );

        $marketStatus =
            $this->isMarketOpenService
                ->isOpen()
            ? 'OPEN'
            : 'CLOSED';

        $prompt =
            $this->buildPrompt(

                marketStatus: $marketStatus,

                template: $template,

                payload: $payload

            );

        $response =
            Http::withToken(

                config(
                    'services.openai.api_key'
                )

            )
                ->timeout(60)
                ->post(
                    'https://api.openai.com/v1/responses',
                    [

                        'model' => config(
                            'services.openai.model',
                            'gpt-4.1-mini'
                        ),

                        'input' => [

                            [

                                'role' => 'system',

                                'content' => 'You are a financial market intelligence engine. Return markdown only.',

                            ],

                            [

                                'role' => 'user',

                                'content' => $prompt,

                            ],

                        ],

                    ]
                );

        if (
            ! $response->successful()
        ) {

            throw new Exception(
                'AI signal generation failed: '.

                $response->status().

                ' - '.

                $response->body()
            );
        }

        $content =
            $response->json(
                'output.0.content.0.text'
            );

        $content = preg_replace(

            '/^```(?:markdown)?\s*/i',

            '',

            $content

        );

        $content = preg_replace(

            '/\s*```$/',

            '',

            $content

        );

        if (
            empty($content)
        ) {

            throw new Exception(
                'AI returned empty signal content.'
            );
        }

        return trim($content);
    }

    /**
     * Build the prompt sent to OpenAI.
     *
     * Templates remain responsible for structure,
     * tone, and formatting.
     *
     * Payloads provide the factual market data
     * the AI should use when generating analysis.
     */
    private function buildPrompt(
        string $marketStatus,
        string $template,
        array $payload
    ): string {

        $prompt =

            "Current Market Status: {$marketStatus}"

            .PHP_EOL

            .PHP_EOL

            .$template;

        if (
            empty($payload)
        ) {

            return $prompt;
        }

        return

            $prompt

            .PHP_EOL

            .PHP_EOL

            .'IMPORTANT:'

            .PHP_EOL

            .'- Use ONLY the supplied market data.'

            .PHP_EOL

            .'- Do not invent values.'

            .PHP_EOL

            .'- Do not reference data that is not provided.'

            .PHP_EOL

            .'- If data is missing, omit discussion of that metric.'

            .PHP_EOL

            .PHP_EOL

            .'MARKET DATA'

            .PHP_EOL

            .json_encode(

                $payload,

                JSON_PRETTY_PRINT

            );
    }

    private function getTemplateContent(
        int $signal_type_id
    ): string {

        $path =
            match ($signal_type_id) {

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

        if (
            ! file_exists($path)
        ) {

            throw new Exception(
                'Template file not found.'
            );
        }

        return file_get_contents(
            $path
        );
    }
}
