<?php

namespace Tests\Feature\ManageUsers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManageUserShowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_admin_can_show_user()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'Managed User',
            'email' => 'managed@example.com',
            'email_verified_at' => now(),
            'is_admin' => 0,
            'is_active' => 1,
            'is_subscriber' => 1,
            'is_influencer' => 0,
            'stripe_id' => 'cus_test_123',
            'pm_type' => 'visa',
            'pm_last_four' => '4242',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/manage-user/'.$user->id);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'user' => [
                    'id' => $user->id,
                    'name' => 'Managed User',
                    'email' => 'managed@example.com',
                    'is_admin' => false,
                    'stripe_id' => 'cus_test_123',
                    'pm_type' => 'visa',
                    'pm_last_four' => '4242',
                ],
            ])
            ->assertJsonPath('user.is_active', 1)
            ->assertJsonPath('user.is_subscriber', 1)
            ->assertJsonPath('user.is_influencer', false)
            ->assertJsonMissing([
                'password',
                'remember_token',
            ]);
    }

    public function test_it_returns_null_when_user_does_not_exist()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/manage-user/999');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'user' => null,
            ]);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/manage-user/1');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
