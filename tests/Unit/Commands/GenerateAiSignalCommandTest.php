<?php

namespace Tests\Unit\Commands;

use App\Models\CronLog;

use App\Models\SignalType;

use Database\Seeders\IntervalSeeder;

use Database\Seeders\NotificationStatusSeeder;

use Database\Seeders\SignalTypeSeeder;

use Database\Seeders\StatusSeeder;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Http;

use Tests\TestCase;

class GenerateAiSignalCommandTest extends TestCase

{

    protected function setUp(): void

    {

        parent::setUp();

        DB::table('ai_market_signals')

            ->truncate();

        DB::table('cron_logs')

            ->truncate();

        DB::table('signal_types')

            ->truncate();

        DB::table('intervals')

            ->truncate();

        DB::table('statuses')

            ->truncate();

        DB::table('notification_statuses')

            ->truncate();

        DB::table('cron_logs')

            ->truncate();

        $this->seed([

            IntervalSeeder::class,

            StatusSeeder::class,

            NotificationStatusSeeder::class,

            SignalTypeSeeder::class,

        ]);

        Http::fake(function ($request) {

            $content =

                strtolower(

                    $request['input'][1]['content']

                );

            /*

            |--------------------------------------------------------------------------

            | Market Mood Prompt

            |--------------------------------------------------------------------------

            */

            if (

                str_contains(

                    $content,

                    'classify the current market mood'

                )

            ) {

                return Http::response([

                    'output' => [

                        [

                            'content' => [

                                [

                                    'text' =>

                                    'Bullish',

                                ],

                            ],

                        ],

                    ],

                ], 200);
            }

            /*

            |--------------------------------------------------------------------------

            | Signal Content Prompts

            |--------------------------------------------------------------------------

            */

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

        DB::table('cron_logs')

            ->truncate();

        DB::table('signal_types')

            ->truncate();

        DB::table('intervals')

            ->truncate();

        DB::table('statuses')

            ->truncate();

        DB::table('notification_statuses')

            ->truncate();

        DB::table('cron_logs')

            ->truncate();

        parent::tearDown();
    }

    public function test_it_generates_all_ai_signals()

    {

        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        $this->assertDatabaseCount(

            'ai_market_signals',

            3

        );

        $this->assertDatabaseHas(

            'ai_market_signals',

            [

                'signal_type_id' =>

                SignalType::MARKET_SNAPSHOT,

            ]

        );

        $this->assertDatabaseHas(

            'ai_market_signals',

            [

                'signal_type_id' =>

                SignalType::MARKET_CONDITIONS,

            ]

        );

        $this->assertDatabaseHas(

            'ai_market_signals',

            [

                'signal_type_id' =>

                SignalType::MARKET_EVENTS,

            ]

        );
    }

    public function test_it_creates_cron_log_record()

    {

        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        $this->assertDatabaseCount(

            'cron_logs',

            1

        );

        $this->assertDatabaseHas(

            'cron_logs',

            [

                'cron_name' =>

                'ai:generate-signals

        {--type=}

        {--force : Force signal generation even if no fresh data exists}',

                'cron_description' =>

                'Generate AI market signals',

            ]

        );
    }

    public function test_it_stores_markdown_content()

    {

        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        $signal =

            DB::table(

                'ai_market_signals'

            )->first();

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

        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        $signal =

            DB::table(

                'ai_market_signals'

            )->first();

        $this->assertNotNull(

            $signal->generated_at

        );
    }

    public function test_it_sets_expires_at()

    {

        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        $signal =

            DB::table(

                'ai_market_signals'

            )->first();

        $this->assertNotNull(

            $signal->expires_at

        );
    }

    public function test_it_sets_signal_as_active()

    {

        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        $this->assertDatabaseHas(

            'ai_market_signals',

            [

                'is_active' => 1,

            ]

        );
    }

    public function test_it_creates_successful_cron_log()

    {

        $this->artisan(

            'ai:generate-signals',

            [

                '--force' => true,

            ]

        )->assertExitCode(0);

        $cronLog =

            CronLog::first();

        $this->assertNotNull(

            $cronLog

        );

        $this->assertNotNull(

            $cronLog->status_id

        );

        $this->assertNotNull(

            $cronLog->run_time

        );

        $this->assertNotNull(

            $cronLog->start_time

        );

        $this->assertNotNull(

            $cronLog->end_time

        );
    }
}
