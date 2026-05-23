<?php

namespace Tests\Feature\AiSignals;

use App\Models\AiMarketSignal;
use App\Models\SignalType;
use App\Models\User;
use Database\Seeders\SignalTypeSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetAiSignalsTest extends TestCase
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

    public function test_it_returns_ai_signals()
    {
        AiMarketSignal::factory()
            ->create([

                'signal_type_id' =>
                SignalType::MARKET_SNAPSHOT,

            ]);

        AiMarketSignal::factory()
            ->create([

                'signal_type_id' =>
                SignalType::MARKET_CONDITIONS,

            ]);

        AiMarketSignal::factory()
            ->create([

                'signal_type_id' =>
                SignalType::MARKET_EVENTS,

            ]);

        $response =
            $this->getJson(
                '/api/get-ai-signals'
            );

        $response->assertStatus(200);

        $response->assertJson([

            'success' => true,

        ]);

        $response->assertJsonStructure([

            'success',

            'market' => [

                'is_open',

                'status',

            ],

            'data',

        ]);

        $this->assertCount(

            3,

            $response->json(
                'data'
            )

        );
    }

    public function test_it_returns_latest_signal_per_type()
    {
        AiMarketSignal::factory()
            ->create([

                'signal_type_id' =>

                SignalType::MARKET_SNAPSHOT,

                'title' =>

                'Old Snapshot',

                'generated_at' =>

                now()->subDay(),

            ]);

        AiMarketSignal::factory()
            ->create([

                'signal_type_id' =>

                SignalType::MARKET_SNAPSHOT,

                'title' =>

                'New Snapshot',

                'generated_at' =>

                now(),

            ]);

        $response =
            $this->getJson(
                '/api/get-ai-signals'
            );

        $response->assertStatus(200);

        $this->assertCount(

            1,

            $response->json(
                'data'
            )

        );

        $this->assertEquals(

            'New Snapshot',

            $response->json(
                'data.0.title'
            )

        );
    }

    public function test_it_returns_signal_type_relationship()
    {
        AiMarketSignal::factory()
            ->create();

        $response =
            $this->getJson(
                '/api/get-ai-signals'
            );

        $response->assertStatus(200);

        $response->assertJsonStructure([

            'success',

            'market' => [

                'is_open',

                'status',

            ],

            'data' => [

                '*' => [

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

            ],

        ]);
    }

    public function test_it_returns_empty_array_when_no_signals_exist()
    {
        $response =
            $this->getJson(
                '/api/get-ai-signals'
            );

        $response->assertStatus(200);

        $response->assertJsonStructure([

            'success',

            'market' => [

                'is_open',

                'status',

            ],

            'data',

        ]);

        $response->assertJson([

            'success' => true,

            'data' => [],

        ]);
    }

    public function test_it_only_returns_active_signals()
    {
        AiMarketSignal::factory()
            ->create([

                'is_active' => false,

            ]);

        AiMarketSignal::factory()
            ->create([

                'is_active' => true,

            ]);

        $response =
            $this->getJson(
                '/api/get-ai-signals'
            );

        $response->assertStatus(200);

        $this->assertCount(

            1,

            $response->json(
                'data'
            )

        );

        $this->assertTrue(

            $response->json(
                'data.0.is_active'
            )

        );
    }
}
