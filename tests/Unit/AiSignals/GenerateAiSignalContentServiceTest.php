<?php

namespace Tests\Unit\AiSignals;

use App\Models\SignalType;
use App\Services\AI\AiSignals\GenerateAiSignalContentService;
use App\Services\AI\AiSignals\IsMarketOpenService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Database\Seeders\SignalTypeSeeder;
use Tests\TestCase;

class GenerateAiSignalContentServiceTest extends TestCase
{
    private GenerateAiSignalContentService
        $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('signal_types')
            ->truncate();

        $this->seed(
            SignalTypeSeeder::class
        );

        $this->service =
            new GenerateAiSignalContentService(

                new IsMarketOpenService()

            );
    }

    protected function tearDown(): void
    {
        DB::table('signal_types')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_generates_market_snapshot_content()
    {
        Http::fake([

            'https://api.openai.com/*' =>

            Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' =>
                                '# Market Snapshot' .
                                    PHP_EOL .
                                    PHP_EOL .
                                    'Bullish momentum continues.',

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);

        $content =
            $this->service->generate(

                SignalType::MARKET_SNAPSHOT

            );

        $this->assertStringContainsString(

            '# Market Snapshot',

            $content

        );
    }

    public function test_it_generates_market_conditions_content()
    {
        Http::fake([

            'https://api.openai.com/*' =>

            Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' =>
                                '# Market Conditions',

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);

        $content =
            $this->service->generate(

                SignalType::MARKET_CONDITIONS

            );

        $this->assertStringContainsString(

            '# Market Conditions',

            $content

        );
    }

    public function test_it_generates_market_events_content()
    {
        Http::fake([

            'https://api.openai.com/*' =>

            Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' =>
                                '# Upcoming Market Events',

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);

        $content =
            $this->service->generate(

                SignalType::MARKET_EVENTS

            );

        $this->assertStringContainsString(

            '# Upcoming Market Events',

            $content

        );
    }

    public function test_it_sends_request_to_openai()
    {
        Http::fake([

            'https://api.openai.com/*' =>

            Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' =>

                                '# Test',

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);
        $this->service->generate(

            SignalType::MARKET_SNAPSHOT

        );

        Http::assertSent(function (
            $request
        ) {

            return
                $request->url() ===
                'https://api.openai.com/v1/responses';
        });
    }

    public function test_it_includes_market_status_in_prompt()
    {
        Http::fake([

            'https://api.openai.com/*' =>

            Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' =>

                                '# Test',

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);
        $this->service->generate(

            SignalType::MARKET_SNAPSHOT

        );

        Http::assertSent(function (
            $request
        ) {

            $content =
                $request['input'][1]['content'];

            return
                str_contains(
                    $content,
                    'Current Market Status:'
                );
        });
    }

    public function test_it_throws_exception_when_openai_fails()
    {
        Http::fake([

            'https://api.openai.com/*' =>

            Http::response([

                'error' => [

                    'message' =>
                    'Server error',

                ],

            ], 500),

        ]);

        $this->expectException(
            \Exception::class
        );

        $this->service->generate(

            SignalType::MARKET_SNAPSHOT

        );
    }

    public function test_it_throws_exception_when_ai_returns_empty_content()
    {
        Http::fake([

            'https://api.openai.com/*' =>

            Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' => '',

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);

        $this->expectException(
            \Exception::class
        );

        $this->service->generate(

            SignalType::MARKET_SNAPSHOT

        );
    }

    public function test_it_throws_exception_for_invalid_signal_type()
    {
        $this->expectException(
            \Exception::class
        );

        $this->service->generate(
            999
        );
    }
}
