<?php

namespace Tests\Unit\Queries\AiSignals;

use App\Models\AiMarketSignal;
use App\Models\SignalType;
use App\Queries\AiSignals\GetLatestAiSignalsQuery;
use Database\Seeders\SignalTypeSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GetLatestAiSignalsQueryTest extends TestCase
{
    private GetLatestAiSignalsQuery $query;

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

        $this->query =
            new GetLatestAiSignalsQuery;
    }

    protected function tearDown(): void
    {
        DB::table('ai_market_signals')
            ->truncate();

        DB::table('signal_types')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_returns_latest_signal_per_type()
    {
        AiMarketSignal::factory()
            ->create([

                'signal_type_id' => SignalType::MARKET_SNAPSHOT,

                'title' => 'Old Snapshot',

                'generated_at' => now()->subDay(),

            ]);

        AiMarketSignal::factory()
            ->create([

                'signal_type_id' => SignalType::MARKET_SNAPSHOT,

                'title' => 'New Snapshot',

                'generated_at' => now(),

            ]);

        $results =
            $this->query->getData();

        $this->assertCount(
            1,
            $results
        );

        $this->assertEquals(
            'New Snapshot',
            $results[0]->title
        );
    }

    public function test_it_returns_multiple_signal_types()
    {
        AiMarketSignal::factory()
            ->create([

                'signal_type_id' => SignalType::MARKET_SNAPSHOT,

            ]);

        AiMarketSignal::factory()
            ->create([

                'signal_type_id' => SignalType::MARKET_CONDITIONS,

            ]);

        AiMarketSignal::factory()
            ->create([

                'signal_type_id' => SignalType::MARKET_EVENTS,

            ]);

        $results =
            $this->query->getData();

        $this->assertCount(
            3,
            $results
        );
    }

    public function test_it_filters_by_signal_type_ids()
    {
        AiMarketSignal::factory()
            ->create([

                'signal_type_id' => SignalType::MARKET_SNAPSHOT,

            ]);

        AiMarketSignal::factory()
            ->create([

                'signal_type_id' => SignalType::MARKET_CONDITIONS,

            ]);

        $results =
            $this->query->getData([

                SignalType::MARKET_SNAPSHOT,

            ]);

        $this->assertCount(
            1,
            $results
        );

        $this->assertEquals(
            SignalType::MARKET_SNAPSHOT,
            $results[0]
                ->signal_type_id
        );
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

        $results =
            $this->query->getData();

        $this->assertCount(
            1,
            $results
        );

        $this->assertTrue(
            $results[0]
                ->is_active
        );
    }

    public function test_it_returns_empty_collection_when_no_signals_exist()
    {
        $results =
            $this->query->getData();

        $this->assertCount(
            0,
            $results
        );
    }

    public function test_it_loads_signal_type_relationship()
    {
        AiMarketSignal::factory()
            ->create([

                'signal_type_id' => SignalType::MARKET_SNAPSHOT,

            ]);

        $results =
            $this->query->getData();

        $this->assertNotNull(
            $results[0]
                ->signalType
        );
    }

    public function test_it_returns_latest_generated_signal()
    {
        AiMarketSignal::factory()
            ->create([

                'title' => 'Older Signal',

                'generated_at' => now()->subHours(2),

            ]);

        AiMarketSignal::factory()
            ->create([

                'title' => 'Newest Signal',

                'generated_at' => now(),

            ]);

        $results =
            $this->query->getData();

        $this->assertEquals(
            'Newest Signal',
            $results[0]->title
        );
    }
}
