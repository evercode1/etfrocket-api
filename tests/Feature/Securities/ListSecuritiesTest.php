<?php

namespace Tests\Feature\Securities;

use App\Models\Security;
use App\Models\SecurityMetric;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListSecuritiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_user_can_list_filtered_securities(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $lowSecurity = Security::factory()->create(['symbol' => 'LOW']);
        $highSecurity = Security::factory()->create(['symbol' => 'HIGH']);
        $middleSecurity = Security::factory()->create(['symbol' => 'MID']);

        $this->createMetric($lowSecurity, ['total_return_percentage' => 5.25]);
        $this->createMetric($highSecurity, ['total_return_percentage' => 22.75]);
        $this->createMetric($middleSecurity, ['total_return_percentage' => 12.50]);

        $response = $this->getJson('/api/list-securities?category=momentum&filter=highest_total_return_percentage&scope=all&range=1y&limit=25');

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.data.0.symbol', 'HIGH');
        $response->assertJsonPath('data.data.1.symbol', 'MID');
        $response->assertJsonPath('data.data.2.symbol', 'LOW');

        $response->assertJsonPath('data.total', 3);
        $response->assertJsonPath('data.per_page', 25);
    }

    public function test_guest_cannot_list_securities(): void
    {
        $response = $this->getJson('/api/list-securities');

        $response->assertStatus(401);
    }

    public function test_it_uses_default_filters_when_no_query_params_are_provided(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $lowSecurity = Security::factory()->create(['symbol' => 'LOW']);
        $highSecurity = Security::factory()->create(['symbol' => 'HIGH']);

        $this->createMetric($lowSecurity, ['total_return_percentage' => 3.00]);
        $this->createMetric($highSecurity, ['total_return_percentage' => 15.00]);

        $response = $this->getJson('/api/list-securities');

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.data.0.symbol', 'HIGH');
        $response->assertJsonPath('data.data.1.symbol', 'LOW');
        $response->assertJsonPath('data.total', 2);
    }

    public function test_it_paginates_filtered_securities(): void
    {
        Carbon::setTestNow('2026-05-15');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        for ($i = 1; $i <= 30; $i++) {
            $security = Security::factory()->create([
                'symbol' => 'SEC'.$i,
            ]);

            $this->createMetric($security, [
                'total_return_percentage' => $i,
            ]);
        }

        $response = $this->getJson('/api/list-securities?category=momentum&filter=highest_total_return_percentage&scope=all&range=1y&limit=10');

        $response->assertStatus(200);

        $response->assertJsonPath('data.per_page', 10);
        $response->assertJsonPath('data.total', 30);
        $response->assertJsonPath('data.last_page', 3);

        $response->assertJsonPath('data.data.0.symbol', 'SEC30');
        $response->assertJsonPath('data.data.9.symbol', 'SEC21');
    }

    public function test_it_returns_500_for_invalid_filter_request(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/list-securities?category=bad-category');

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }

    private function createMetric(Security $security, array $overrides = []): SecurityMetric
    {
        return SecurityMetric::factory()->create(array_merge([
            'security_id' => $security->id,
            'performance_range_type_id' => 1,

            'start_date' => Carbon::now()->subDays(30)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),

            'start_price' => 100,
            'end_price' => 110,
            'price_change' => 10,
            'price_change_percentage' => 10,

            'dividends_paid' => 0,
            'dividend_count' => 0,
            'average_dividend' => 0,

            'total_return_percentage' => 10,

            'start_nav' => 100,
            'end_nav' => 110,
            'nav_change' => 10,
            'nav_erosion_percentage' => 0,
            'nav_direction_id' => 1,

            'start_aum' => 1000000000,
            'end_aum' => 1100000000,
            'aum_change' => 100000000,
            'aum_change_percentage' => 10,
            'aum_direction_id' => 1,

            'calculated_at' => Carbon::now(),
        ], $overrides));
    }
}
