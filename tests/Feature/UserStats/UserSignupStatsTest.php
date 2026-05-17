<?php

namespace Tests\Feature\UserStats;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserSignupStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('personal_access_tokens')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow(Carbon::parse('2026-05-16 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        DB::table('personal_access_tokens')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_admin_can_get_default_seven_day_user_signup_stats(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
            'created_at' => now(),
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($admin, ['*']);

        User::factory()->create([
            'created_at' => '2026-05-16 10:00:00',
            'email_verified_at' => '2026-05-16 10:30:00',
        ]);

        User::factory()->create([
            'created_at' => '2026-05-14 10:00:00',
            'email_verified_at' => null,
        ]);

        User::factory()->create([
            'created_at' => '2026-05-10 10:00:00',
            'email_verified_at' => '2026-05-10 10:30:00',
        ]);

        User::factory()->create([
            'created_at' => '2026-05-01 10:00:00',
            'email_verified_at' => '2026-05-01 10:30:00',
        ]);

        $response = $this->getJson('/api/user-signup-stats');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('range', '7d')
            ->assertJsonPath('total_users', 5)
            ->assertJsonPath('total_verified_users', 4)
            ->assertJsonPath('range_total', 4)
            ->assertJsonPath('range_verified_total', 3)
            ->assertJsonPath('available_ranges', [
                '1d',
                '7d',
                '30d',
                '90d',
                '1y',
                'max',
            ]);

        $data = collect($response->json('data'));

        $this->assertSame('2026-05-10', $data->first()['period']);
        $this->assertSame('2026-05-16', $data->last()['period']);

        $this->assertSame(
            1,
            $data->firstWhere('period', '2026-05-10')['signups']
        );

        $this->assertSame(
            1,
            $data->firstWhere('period', '2026-05-10')['verified']
        );

        $this->assertSame(
            1,
            $data->firstWhere('period', '2026-05-14')['signups']
        );

        $this->assertSame(
            0,
            $data->firstWhere('period', '2026-05-14')['verified']
        );

        $this->assertSame(
            2,
            $data->firstWhere('period', '2026-05-16')['signups']
        );

        $this->assertSame(
            2,
            $data->firstWhere('period', '2026-05-16')['verified']
        );
    }

    public function test_admin_can_get_thirty_day_user_signup_stats(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($admin, ['*']);

        User::factory()->create([
            'created_at' => '2026-05-16 09:00:00',
        ]);

        User::factory()->create([
            'created_at' => '2026-05-10 09:00:00',
        ]);

        User::factory()->create([
            'created_at' => '2026-04-20 09:00:00',
        ]);

        User::factory()->create([
            'created_at' => '2026-03-01 09:00:00',
        ]);

        $response = $this->getJson('/api/user-signup-stats?range=30d');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('range', '30d')
            ->assertJsonPath('total_users', 5)
            ->assertJsonPath('range_total', 4);

        $data = collect($response->json('data'));

        $this->assertSame('2026-04-17', $data->first()['period']);
        $this->assertSame('2026-05-16', $data->last()['period']);

        $this->assertSame(
            1,
            $data->firstWhere('period', '2026-05-10')['signups']
        );

        $this->assertSame(
            2,
            $data->firstWhere('period', '2026-05-16')['signups']
        );

        $this->assertSame(
            1,
            $data->firstWhere('period', '2026-04-20')['signups']
        );
    }

    public function test_admin_can_get_one_day_user_signup_stats_grouped_by_hour(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($admin, ['*']);

        User::factory()->create([
            'created_at' => '2026-05-16 10:15:00',
        ]);

        User::factory()->create([
            'created_at' => '2026-05-16 10:45:00',
        ]);

        User::factory()->create([
            'created_at' => '2026-05-15 13:00:00',
        ]);

        User::factory()->create([
            'created_at' => '2026-05-14 13:00:00',
        ]);

        $response = $this->getJson('/api/user-signup-stats?range=1d');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('range', '1d')
            ->assertJsonPath('total_users', 5)
            ->assertJsonPath('range_total', 4);

        $data = collect($response->json('data'));

        $this->assertSame('2026-05-15 12:00:00', $data->first()['period']);
        $this->assertSame('2026-05-16 12:00:00', $data->last()['period']);

        $this->assertSame(
            2,
            $data->firstWhere('period', '2026-05-16 10:00:00')['signups']
        );

        $this->assertSame(
            1,
            $data->firstWhere('period', '2026-05-15 13:00:00')['signups']
        );
    }

    public function test_admin_can_get_max_user_signup_stats(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($admin, ['*']);

        User::factory()->create([
            'created_at' => '2023-01-10 10:00:00',
        ]);

        User::factory()->create([
            'created_at' => '2023-01-20 10:00:00',
        ]);

        User::factory()->create([
            'created_at' => '2024-06-10 10:00:00',
        ]);

        $response = $this->getJson('/api/user-signup-stats?range=max');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('range', 'max')
            ->assertJsonPath('total_users', 4)
            ->assertJsonPath('range_total', 4);

        $data = collect($response->json('data'));

        $this->assertSame('2023-01', $data->first()['period']);
        $this->assertSame('2026-05', $data->last()['period']);

        $this->assertSame(
            2,
            $data->firstWhere('period', '2023-01')['signups']
        );

        $this->assertSame(
            1,
            $data->firstWhere('period', '2024-06')['signups']
        );
    }

    public function test_invalid_range_defaults_to_seven_days(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/user-signup-stats?range=bad-range');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('range', '7d');
    }

    public function test_guest_cannot_get_user_signup_stats(): void
    {
        $response = $this->getJson('/api/user-signup-stats');

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_get_user_signup_stats(): void
    {
        $user = User::factory()->create([
            'is_admin' => 0,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/user-signup-stats');

        $response->assertStatus(401);
    }
}
