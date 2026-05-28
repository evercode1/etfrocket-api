<?php

namespace Tests\Feature\ManageUsers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManageUsersListTest extends TestCase
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

    public function test_admin_can_list_users()
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'Managed User',
            'email' => 'managed@example.com',
            'is_admin' => 0,
            'is_active' => 1,
            'is_subscriber' => 1,
            'is_influencer' => 0,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/manage-users');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'section_heading' => 'Manage Users',
                'edit_endpoint' => 'manage-user/edit/',
                'details_endpoint' => 'manage-user/',
                'delete_endpoint' => 'delete-user/',
                'search_endpoint' => 'manage-users/search/',
            ])
            ->assertJsonPath('users.per_page', 10)
            ->assertJsonFragment([
                'id' => $user->id,
                'name' => 'Managed User',
                'email' => 'managed@example.com',
            ])
            ->assertJsonMissing([
                'password',
                'remember_token',
            ]);
    }

    public function test_it_sorts_users_by_selected_column_descending()
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'is_admin' => 1,
        ]);

        User::factory()->create([
            'name' => 'Alpha User',
            'email' => 'alpha@example.com',
        ]);

        User::factory()->create([
            'name' => 'Zulu User',
            'email' => 'zulu@example.com',
        ]);

        Sanctum::actingAs($admin, ['*']);

        // sortBy=2 maps to users.name
        $response = $this->getJson('/api/manage-users?sortBy=2&sortOrder=desc');

        $response->assertOk();

        $this->assertEquals(
            'Zulu User',
            $response->json('users.data.0.name')
        );
    }

    public function test_it_paginates_users()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        User::factory()->count(15)->create();

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/manage-users');

        $response->assertOk()
            ->assertJsonPath('users.per_page', 10)
            ->assertJsonCount(10, 'users.data');
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/manage-users');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
