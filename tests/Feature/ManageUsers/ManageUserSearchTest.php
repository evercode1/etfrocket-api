<?php

namespace Tests\Feature\ManageUsers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManageUserSearchTest extends TestCase
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

    public function test_admin_can_search_users_by_name()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $matchedUser = User::factory()->create([
            'name' => 'John Dividend',
            'email' => 'john@example.com',
        ]);

        User::factory()->create([
            'name' => 'Jane Investor',
            'email' => 'jane@example.com',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/manage-users/search/Dividend');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'section_heading' => 'Manage Users',
                'edit_endpoint' => 'manage-user/edit/',
                'details_endpoint' => 'manage-user/',
                'delete_endpoint' => 'delete-user/',
            ])
            ->assertJsonPath('users.per_page', 10)
            ->assertJsonFragment([
                'id' => $matchedUser->id,
                'name' => 'John Dividend',
                'email' => 'john@example.com',
            ])
            ->assertJsonMissing([
                'name' => 'Jane Investor',
                'email' => 'jane@example.com',
            ]);
    }

    public function test_admin_can_search_users_by_email()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $matchedUser = User::factory()->create([
            'name' => 'Email Match',
            'email' => 'matched@example.com',
        ]);

        User::factory()->create([
            'name' => 'Other User',
            'email' => 'other@example.com',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/manage-users/search/matched@example.com');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $matchedUser->id,
                'name' => 'Email Match',
                'email' => 'matched@example.com',
            ])
            ->assertJsonMissing([
                'email' => 'other@example.com',
            ]);
    }

    public function test_it_sorts_search_results_by_selected_column_descending()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        User::factory()->create([
            'name' => 'Alpha Match',
            'email' => 'alpha-match@example.com',
        ]);

        User::factory()->create([
            'name' => 'Zulu Match',
            'email' => 'zulu-match@example.com',
        ]);

        Sanctum::actingAs($admin, ['*']);

        // sortBy=2 maps to users.name
        $response = $this->getJson('/api/manage-users/search/Match?sortBy=2&sortOrder=desc');

        $response->assertOk();

        $this->assertEquals(
            'Zulu Match',
            $response->json('users.data.0.name')
        );
    }

    public function test_it_returns_empty_paginated_result_when_no_users_match()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/manage-users/search/NoMatch');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonCount(0, 'users.data');
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/manage-users/search/test');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
