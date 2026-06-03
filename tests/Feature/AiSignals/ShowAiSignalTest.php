<?php

namespace Tests\Feature\AiSignals;

use App\Models\AiMarketSignal;
use App\Models\SignalType;
use App\Models\User;
use Database\Seeders\SignalTypeSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowAiSignalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ai_market_signals')
            ->truncate();

        DB::table('signal_types')
            ->truncate();

        DB::table('users')
            ->truncate();

        $this->seed(
            SignalTypeSeeder::class
        );

        $user =
            User::factory()
                ->create();

        Sanctum::actingAs(

            $user,

            ['*']

        );
    }

    protected function tearDown(): void
    {
        DB::table('ai_market_signals')
            ->truncate();

        DB::table('signal_types')
            ->truncate();

        DB::table('users')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_returns_ai_signal()
    {
        $signal =
            AiMarketSignal::factory()
                ->create([

                    'signal_type_id' => SignalType::MARKET_SNAPSHOT,

                    'title' => 'AI Market Snapshot',

                ]);

        $response =
            $this->getJson(

                "/api/show-ai-signal/{$signal->id}"

            );

        $response->assertStatus(200);

        $response->assertJson([

            'success' => true,

            'data' => [

                'id' => $signal->id,

                'title' => 'AI Market Snapshot',

            ],

        ]);
    }

    public function test_it_returns_signal_type_relationship()
    {
        $signal =
            AiMarketSignal::factory()
                ->create();

        $response =
            $this->getJson(

                "/api/show-ai-signal/{$signal->id}"

            );

        $response->assertStatus(200);

        $response->assertJsonStructure([

            'success',

            'data' => [

                'id',

                'signal_type_id',

                'title',

                'subtitle',

                'market_mood',

                'confidence_score',

                'markdown_content',

                'payload_json',

                'generated_at',

                'expires_at',

                'is_active',

                'ai_model',

                'signal_type',

            ],

        ]);
    }

    public function test_it_returns_not_found_for_invalid_id()
    {
        $response =
            $this->getJson(
                '/api/show-ai-signal/999999'
            );

        $response->assertStatus(404);

        $response->assertJson([

            'success' => false,

        ]);
    }

    public function test_it_returns_etf_watchlist_signal()
    {
        $signal =
            AiMarketSignal::factory()
                ->create([

                    'signal_type_id' => SignalType::ETF_WATCHLIST,

                    'title' => 'AI ETF Watchlist',

                ]);

        $response =
            $this->getJson(

                "/api/show-ai-signal/{$signal->id}"

            );

        $response->assertStatus(200);

        $response->assertJson([

            'success' => true,

            'data' => [

                'id' => $signal->id,

                'title' => 'AI ETF Watchlist',

                'signal_type_id' => SignalType::ETF_WATCHLIST,

            ],

        ]);
    }
}
