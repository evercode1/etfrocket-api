<?php

namespace Tests\Unit\Commands\Handlers;

use App\Models\AiMarketSignal;
use App\Models\ImportLog;
use App\Models\ImportType;
use App\Models\SignalType;
use App\Models\Status;
use App\Services\AI\AiSignals\IsMarketOpenService;
use App\Services\Crons\Handlers\GenerateAiSignalsHandler;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\SignalTypeSeeder;
use Database\Seeders\StatusSeeder;
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

        DB::table('import_logs')->truncate();
        DB::table('import_types')->truncate();
        DB::table('ai_market_signals')->truncate();
        DB::table('signal_types')->truncate();
        DB::table('statuses')->truncate();

        $this->seed([

            SignalTypeSeeder::class,

            ImportTypeSeeder::class,

            StatusSeeder::class,

        ]);

        $isMarketOpenService =

            $this->mock(

                IsMarketOpenService::class

            );

        $isMarketOpenService

            ->shouldReceive('isOpen')

            ->andReturn(true);

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

        DB::table('import_logs')->truncate();
        DB::table('import_types')->truncate();
        DB::table('ai_market_signals')->truncate();
        DB::table('signal_types')->truncate();
        DB::table('statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_generates_all_ai_signals()
    {

        $results =

            $this->handler

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

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

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

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

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

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

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

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

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

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

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

        $signal =

            AiMarketSignal::first();

        $this->assertNotNull(

            $signal->generated_at

        );
    }

    public function test_it_sets_expires_at()
    {

        $this->handler

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

        $signal =

            AiMarketSignal::first();

        $this->assertNotNull(

            $signal->expires_at

        );
    }

    public function test_it_sets_signal_as_active()
    {

        $this->handler

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

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

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertNull(

            $results['cron_fail_details']

        );
    }

    public function test_it_creates_import_logs_for_each_signal_type()
    {

        $this->handler

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

        $this->assertDatabaseHas(

            'import_logs',

            [

                'import_type_id' =>

                ImportType::MARKET_SNAPSHOT,

                'status_id' =>

                Status::COMPLETED,

            ]

        );

        $this->assertDatabaseHas(

            'import_logs',

            [

                'import_type_id' =>

                ImportType::MARKET_CONDITIONS,

                'status_id' =>

                Status::COMPLETED,

            ]

        );

        $this->assertDatabaseHas(

            'import_logs',

            [

                'import_type_id' =>

                ImportType::MARKET_EVENTS,

                'status_id' =>

                Status::COMPLETED,

            ]

        );
    }

    public function test_it_logs_generated_markdown()
    {

        $this->handler

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

        $log =

            ImportLog::where(

                'import_type_id',

                ImportType::MARKET_SNAPSHOT

            )->first();

        $this->assertNotNull(

            $log

        );

        $this->assertStringContainsString(

            '#',

            $log->generated_markdown

        );
    }

    public function test_it_logs_processing_notes()
    {

        $this->handler

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

        $log =

            ImportLog::first();

        $this->assertNotNull(

            $log

        );

        $this->assertStringContainsString(

            'Forced AI signal generation executed successfully.',

            $log->processing_notes

        );
    }

    public function test_it_logs_runtime_and_processing_counts()
    {

        $this->handler

            ->handleGenerateAiSignals([

                'force' => true,

            ]);

        $log =

            ImportLog::first();

        $this->assertNotNull(

            $log

        );

        $this->assertEquals(

            1,

            $log->rows_processed

        );

        $this->assertEquals(

            1,

            $log->records_created

        );

        $this->assertEquals(

            0,

            $log->failure_count

        );

        $this->assertGreaterThanOrEqual(

            0,

            $log->run_time

        );
    }
}
