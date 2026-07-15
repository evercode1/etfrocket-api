<?php

namespace Tests\Feature\Securities;

use App\Models\Security;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetSecuritySelectsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_details')->truncate();
        DB::table('securities')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_details')->truncate();
        DB::table('securities')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_security_select_options_ordered_by_symbol(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $third = Security::factory()->create([
            'symbol' => 'YMAX',
        ]);

        $first = Security::factory()->create([
            'symbol' => 'JEPI',
        ]);

        $second = Security::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $response = $this->getJson('/api/get-security-selects');

        $response->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [
                    [
                        'id' => $first->id,
                        'symbol' => $first->symbol,
                    ],
                    [
                        'id' => $second->id,
                        'symbol' => $second->symbol,
                    ],
                    [
                        'id' => $third->id,
                        'symbol' => $third->symbol,
                    ],
                ],
            ]);
    }

    public function test_it_returns_empty_data_when_no_securities_exist(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $response = $this->getJson('/api/get-security-selects');

        $response->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [],
            ]);
    }
}
