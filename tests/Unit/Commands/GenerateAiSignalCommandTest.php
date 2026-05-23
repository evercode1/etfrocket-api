<?php

namespace Tests\Unit\Commands;

use App\Models\SignalType;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GenerateAiSignalCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ai_market_signals')
            ->truncate();

        DB::table('signal_types')
            ->truncate();

        $this->seed(
            \Database\Seeders\SignalTypeSeeder::class
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
        $this->artisan(
            'ai:generate-signals'
        )

            ->expectsOutput(
                '[1] Generated: AI Market Snapshot'
            )

            ->expectsOutput(
                '[2] Generated: AI Market Conditions'
            )

            ->expectsOutput(
                '[3] Generated: AI Market Events'
            )

            ->expectsOutput(
                'AI signals generated successfully.'
            )

            ->assertExitCode(0);

        $this->assertDatabaseCount(
            'ai_market_signals',
            3
        );
    }

    public function test_it_generates_only_market_snapshot()
    {
        $this->artisan(
            'ai:generate-signals',
            [

                '--type' =>
                SignalType::MARKET_SNAPSHOT,

            ]
        )

            ->expectsOutput(
                '[1] Generated: AI Market Snapshot'
            )

            ->assertExitCode(0);

        $this->assertDatabaseCount(
            'ai_market_signals',
            1
        );

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

    public function test_it_generates_only_market_conditions()
    {
        $this->artisan(
            'ai:generate-signals',
            [

                '--type' =>
                SignalType::MARKET_CONDITIONS,

            ]
        )

            ->expectsOutput(
                '[1] Generated: AI Market Conditions'
            )

            ->assertExitCode(0);

        $this->assertDatabaseCount(
            'ai_market_signals',
            1
        );

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

    public function test_it_generates_only_market_events()
    {
        $this->artisan(
            'ai:generate-signals',
            [

                '--type' =>
                SignalType::MARKET_EVENTS,

            ]
        )

            ->expectsOutput(
                '[1] Generated: AI Market Events'
            )

            ->assertExitCode(0);

        $this->assertDatabaseCount(
            'ai_market_signals',
            1
        );

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
        $this->artisan(
            'ai:generate-signals',
            [

                '--type' =>
                SignalType::MARKET_SNAPSHOT,

            ]
        );

        $signal =
            DB::table(
                'ai_market_signals'
            )->first();

        $this->assertStringContainsString(
            '# Market Snapshot',
            $signal->markdown_content
        );
    }

    public function test_it_sets_generated_at()
    {
        $this->artisan(
            'ai:generate-signals',
            [

                '--type' =>
                SignalType::MARKET_SNAPSHOT,

            ]
        );

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

                '--type' =>
                SignalType::MARKET_SNAPSHOT,

            ]
        );

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

                '--type' =>
                SignalType::MARKET_SNAPSHOT,

            ]
        );

        $this->assertDatabaseHas(
            'ai_market_signals',
            [

                'is_active' => 1,

            ]
        );
    }
}
