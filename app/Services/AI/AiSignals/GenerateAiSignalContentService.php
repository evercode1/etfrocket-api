<?php

namespace App\Services\AI\AiSignals;

use App\Models\SignalType;
use Exception;
use Illuminate\Support\Facades\Http;

class GenerateAiSignalContentService
{
    public function __construct(

        private IsMarketOpenService
        $isMarketOpenService

    ) {}

    public function generate(
        int $signal_type_id
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

                            'content' =>
                            'You are a financial market intelligence engine. Return markdown only.',

                        ],

                        [

                            'role' => 'user',

                            'content' =>

                            "Current Market Status: {$marketStatus}" .

                                PHP_EOL .

                                PHP_EOL .

                                $template,

                        ],

                    ],

                ]
            );

        if (
            !$response->successful()
        ) {

            throw new Exception(

                'AI signal generation failed: ' .

                    $response->status() .

                    ' - ' .

                    $response->body()

            );
        }

        $content =
            $response->json(
                'output.0.content.0.text'
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

    private function getTemplateContent(
        int $signal_type_id
    ): string {

        $path =
            match ($signal_type_id) {

                SignalType::MARKET_SNAPSHOT =>

                app_path(
                    'Services/AI/AiSignals/Templates/market_snapshot.md'
                ),

                SignalType::MARKET_CONDITIONS =>

                app_path(
                    'Services/AI/AiSignals/Templates/market_conditions.md'
                ),

                SignalType::MARKET_EVENTS =>

                app_path(
                    'Services/AI/AiSignals/Templates/market_events.md'
                ),

                default =>

                throw new Exception(
                    'Invalid signal type.'
                ),
            };

        if (
            !file_exists($path)
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
