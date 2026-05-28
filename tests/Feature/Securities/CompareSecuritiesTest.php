<?php

namespace Tests\Feature\Securities;

use App\Models\Security;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompareSecuritiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_user_can_compare_securities(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $schd = Security::factory()->create([
            'symbol' => 'SCHD',

        ]);

        $vym = Security::factory()->create([
            'symbol' => 'VYM',

        ]);

        $this->createPriceHistory($schd->id, '2026-05-13', 78.12);
        $this->createPriceHistory($schd->id, '2026-05-14', 78.44);

        $this->createPriceHistory($vym->id, '2026-05-13', 119.25);
        $this->createPriceHistory($vym->id, '2026-05-14', 120.10);

        $response = $this->getJson(
            "/api/compare-securities?metric=price&range=30d&security_ids={$schd->id},{$vym->id}"
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.metric', 'price');
        $response->assertJsonPath('data.range', '30d');

        $response->assertJsonPath('data.series.0.security_id', $schd->id);
        $response->assertJsonPath('data.series.0.symbol', 'SCHD');
        $response->assertJsonPath('data.series.0.security_name', 'SCHD_name');

        $response->assertJsonPath('data.series.0.points.0.date', '2026-05-13');
        $response->assertJsonPath('data.series.0.points.0.value', '78.1200');
        $response->assertJsonPath('data.series.0.points.1.date', '2026-05-14');
        $response->assertJsonPath('data.series.0.points.1.value', '78.4400');

        $response->assertJsonPath('data.series.1.security_id', $vym->id);
        $response->assertJsonPath('data.series.1.symbol', 'VYM');

        $response->assertJsonPath('data.series.1.points.0.date', '2026-05-13');
        $response->assertJsonPath('data.series.1.points.0.value', '119.2500');
        $response->assertJsonPath('data.series.1.points.1.date', '2026-05-14');
        $response->assertJsonPath('data.series.1.points.1.value', '120.1000');
    }

    public function test_guest_cannot_compare_securities(): void
    {
        $response = $this->getJson('/api/compare-securities');

        $response->assertStatus(401);
    }

    public function test_compare_securities_uses_default_metric_and_range(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $security = Security::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $this->createPriceHistory($security->id, '2026-05-14', 78.44);

        $response = $this->getJson(
            "/api/compare-securities?security_ids={$security->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonPath('data.metric', 'price');
        $response->assertJsonPath('data.range', '1y');
        $response->assertJsonPath('data.series.0.symbol', 'SCHD');
        $response->assertJsonPath('data.series.0.points.0.date', '2026-05-14');
        $response->assertJsonPath('data.series.0.points.0.value', '78.4400');
    }

    public function test_compare_securities_filters_history_by_range(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $security = Security::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $this->createPriceHistory($security->id, '2026-04-01', 75.00);
        $this->createPriceHistory($security->id, '2026-05-01', 77.00);
        $this->createPriceHistory($security->id, '2026-05-14', 78.00);

        $response = $this->getJson(
            "/api/compare-securities?metric=price&range=30d&security_ids={$security->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonPath('data.series.0.points.0.date', '2026-05-01');
        $response->assertJsonPath('data.series.0.points.1.date', '2026-05-14');

        $this->assertCount(2, $response->json('data.series.0.points'));
    }

    public function test_compare_securities_preserves_requested_security_order(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $aaa = Security::factory()->create(['symbol' => 'AAA']);
        $bbb = Security::factory()->create(['symbol' => 'BBB']);

        $this->createPriceHistory($aaa->id, '2026-05-14', 10.00);
        $this->createPriceHistory($bbb->id, '2026-05-14', 20.00);

        $response = $this->getJson(
            "/api/compare-securities?metric=price&range=30d&security_ids={$bbb->id},{$aaa->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonPath('data.series.0.symbol', 'BBB');
        $response->assertJsonPath('data.series.1.symbol', 'AAA');
    }

    public function test_compare_securities_returns_500_for_invalid_metric(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson(
            '/api/compare-securities?metric=bad_metric&range=30d&security_ids=1,2'
        );

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }

    public function test_compare_securities_returns_500_when_no_securities_are_provided(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/compare-securities?metric=price&range=30d');

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }

    private function createPriceHistory(int $securityId, string $date, float $closePrice): void
    {
        DB::table('security_price_histories')->insert([
            'security_id' => $securityId,
            'price_date' => $date,
            'close_price' => $closePrice,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
