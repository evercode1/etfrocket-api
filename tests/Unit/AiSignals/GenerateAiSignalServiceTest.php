<?php

namespace Tests\Unit\AiSignals;

use App\Models\AiMarketSignal;
use App\Models\SignalType;
use App\Services\AI\AiSignals\GenerateAiSignalContentService;
use App\Services\AI\AiSignals\GenerateAiSignalService;
use App\Services\AI\AiSignals\IsMarketOpenService;
use Database\Seeders\SignalTypeSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GenerateAiSignalServiceTest extends TestCase
{
    private GenerateAiSignalService
        $service;

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

        $this->service =

            new GenerateAiSignalService(

                new GenerateAiSignalContentService(

                    new IsMarketOpenService()

                )

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
    }

    protected function tearDown(): void
    {
        DB::table('ai_market_signals')
            ->truncate();

        DB::table('signal_types')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_generates_market_snapshot_signal()
    {
        $signal =
            $this->service->generate(

                SignalType::MARKET_SNAPSHOT

            );

        $this->assertInstanceOf(
            AiMarketSignal::class,
            $signal
        );

        $this->assertEquals(
            SignalType::MARKET_SNAPSHOT,
            $signal->signal_type_id
        );

        $this->assertEquals(
            'AI Market Snapshot',
            $signal->title
        );

        $this->assertStringContainsString(
            '# Market Snapshot',
            $signal->markdown_content
        );

        $this->assertTrue(
            $signal->is_active
        );
    }

    public function test_it_generates_market_conditions_signal()
    {
        $signal =
            $this->service->generate(

                SignalType::MARKET_CONDITIONS

            );

        $this->assertEquals(
            SignalType::MARKET_CONDITIONS,
            $signal->signal_type_id
        );

        $this->assertEquals(
            'AI Market Conditions',
            $signal->title
        );

        $this->assertStringContainsString(
            '# Market Conditions',
            $signal->markdown_content
        );
    }

    public function test_it_generates_market_events_signal()
    {
        $signal =
            $this->service->generate(

                SignalType::MARKET_EVENTS

            );

        $this->assertEquals(
            SignalType::MARKET_EVENTS,
            $signal->signal_type_id
        );

        $this->assertEquals(
            'AI Market Events',
            $signal->title
        );

        $this->assertStringContainsString(
            '# Upcoming Market Events',
            $signal->markdown_content
        );
    }

    public function test_it_stores_record_in_database()
    {
        $this->service->generate(

            SignalType::MARKET_SNAPSHOT

        );

        $this->assertDatabaseCount(
            'ai_market_signals',
            1
        );
    }

    public function test_it_sets_generated_at()
    {
        $signal =
            $this->service->generate(

                SignalType::MARKET_SNAPSHOT

            );

        $this->assertNotNull(
            $signal->generated_at
        );
    }

    public function test_it_sets_expires_at()
    {
        $signal =
            $this->service->generate(

                SignalType::MARKET_SNAPSHOT

            );

        $this->assertNotNull(
            $signal->expires_at
        );
    }

    public function test_it_sets_confidence_score()
    {
        $signal =
            $this->service->generate(

                SignalType::MARKET_SNAPSHOT

            );

        $this->assertGreaterThanOrEqual(
            72,
            $signal->confidence_score
        );

        $this->assertLessThanOrEqual(
            94,
            $signal->confidence_score
        );
    }

    public function test_it_sets_payload_json()
    {
        $signal =
            $this->service->generate(

                SignalType::MARKET_SNAPSHOT

            );

        $this->assertIsArray(
            $signal->payload_json
        );

        $this->assertArrayHasKey(
            'template_used',
            $signal->payload_json
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
