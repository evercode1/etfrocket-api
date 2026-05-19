<?php

namespace Tests\Feature\Etfs;

use App\Models\Etf;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetEtfSelectsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_etf_select_options_ordered_by_symbol(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $third = Etf::factory()->create([
            'symbol' => 'YMAX',
        ]);

        $first = Etf::factory()->create([
            'symbol' => 'JEPI',
        ]);

        $second = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $response = $this->getJson('/api/get-etf-selects');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    (string) $first->id => $first->symbol,
                    (string) $second->id => $second->symbol,
                    (string) $third->id => $third->symbol,
                ],
            ]);

        $data = $response->json('data');

        $this->assertSame(

            [

                $first->id,

                $second->id,

                $third->id,

            ],

            array_keys($data)

        );
    }

    public function test_it_returns_empty_data_when_no_etfs_exist(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $response = $this->getJson('/api/get-etf-selects');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }
}
