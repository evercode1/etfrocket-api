<?php

namespace Tests\Unit\Services\Crons\Handlers;

use App\Models\AiMarketSignal;
use App\Models\SignalType;
use App\Services\Crons\Handlers\GenerateAiSignalsHandler;
use Database\Seeders\SignalTypeSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GenerateAiSignalsHandlerTest extends TestCase
{
    private GenerateAiSignalsHandler
        $handler;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ai_market_signals')
            ->truncate();

        DB::table('signal_types')
            ->truncate();

        $this->seed(
            SignalTypeSeeder::class
        );

        Http::fake(function ($request) {

            $content =
                strtolower(
                    $request['input'][1]['content']
                );

            if (
                str_contains(
                    $content,
                    'market snapshot'
                )
            ) {

                return Http::response([

                    'output' => [

                        [

                            'content' => [

                                [

                                    'text' =>
                                    '# Market Snapshot',

                                ],

                            ],

                        ],

                    ],

                ], 200);
            }

            if (
                str_contains(
                    $content,
                    'market conditions'
                )
            ) {

                return Http::response([

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

                ], 200);
            }

            if (
                str_contains(
                    $content,
                    'market events'
                )
            ) {

                return Http::response([

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

                ], 200);
            }

            return Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' =>
                                '# AI Signal',

                            ],

                        ],

                    ],

                ],

            ], 200);
        });

        $this->handler =
            app(
                GenerateAiSignalsHandler::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('ai_market_signals')
            ->truncate();

        DB::table('signal_types')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_generates_all_ai_signals()
    {
        $results =
            $this->handler
            ->handleGenerateAiSignals();

        $this->assertEquals(
            1,
            $results['success']
        );

        $this->assertEquals(
            3,
            AiMarketSignal::count()
        );
    }

    public function test_it_generates_market_snapshot_signal()
    {
        $this->handler
            ->handleGenerateAiSignals();

        $this->assertDatabaseHas(
            'ai_market_signals',
            [

                'signal_type_id' =>
                SignalType::MARKET_SNAPSHOT,

                'title' =>
                'AI Market Snapshot',

            ]
        );
    }

    public function test_it_generates_market_conditions_signal()
    {
        $this->handler
            ->handleGenerateAiSignals();

        $this->assertDatabaseHas(
            'ai_market_signals',
            [

                'signal_type_id' =>
                SignalType::MARKET_CONDITIONS,

                'title' =>
                'AI Market Conditions',

            ]
        );
    }

    public function test_it_generates_market_events_signal()
    {
        $this->handler
            ->handleGenerateAiSignals();

        $this->assertDatabaseHas(
            'ai_market_signals',
            [

                'signal_type_id' =>
                SignalType::MARKET_EVENTS,

                'title' =>
                'AI Market Events',

            ]
        );
    }

    public function test_it_stores_markdown_content()
    {
        $this->handler
            ->handleGenerateAiSignals();

        $signal =
            AiMarketSignal::first();

        $this->assertNotNull(
            $signal
        );

        $this->assertStringContainsString(
            '#',
            $signal->markdown_content
        );
    }

    public function test_it_sets_generated_at()
    {
        $this->handler
            ->handleGenerateAiSignals();

        $signal =
            AiMarketSignal::first();

        $this->assertNotNull(
            $signal->generated_at
        );
    }

    public function test_it_sets_expires_at()
    {
        $this->handler
            ->handleGenerateAiSignals();

        $signal =
            AiMarketSignal::first();

        $this->assertNotNull(
            $signal->expires_at
        );
    }

    public function test_it_sets_signal_as_active()
    {
        $this->handler
            ->handleGenerateAiSignals();

        $this->assertDatabaseHas(
            'ai_market_signals',
            [

                'is_active' => 1,

            ]
        );
    }

    public function test_it_returns_success_response()
    {
        $results =
            $this->handler
            ->handleGenerateAiSignals();

        $this->assertEquals(
            1,
            $results['success']
        );

        $this->assertNull(
            $results['cron_fail_details']
        );
    }
}
