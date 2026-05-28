<?php

namespace Tests\Feature\ManageUsers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManageUpdateUserTest extends TestCase
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

    public function test_admin_can_update_user()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'is_admin' => 0,
            'is_active' => 0,
            'is_subscriber' => 0,
            'is_influencer' => 0,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/manage-user/'.$user->id, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_admin' => 1,
            'is_active' => 1,
            'is_subscriber' => 1,
            'is_influencer' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'user updated',
                'user' => [
                    'id' => $user->id,
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                ],
            ]);

        $user->refresh();

        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertTrue((bool) $user->is_admin);
        $this->assertTrue((bool) $user->is_active);
        $this->assertTrue((bool) $user->is_subscriber);
        $this->assertTrue((bool) $user->is_influencer);
    }

    public function test_it_allows_user_to_keep_same_email()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'same@example.com',
            'is_admin' => 0,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/manage-user/'.$user->id, [
            'name' => 'New Name',
            'email' => 'same@example.com',
            'is_admin' => 0,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'user updated',
            ]);

        $user->refresh();

        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('same@example.com', $user->email);
    }

    public function test_it_rejects_duplicate_email()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/manage-user/'.$user->id, [
            'name' => 'Updated Name',
            'email' => 'taken@example.com',
            'is_admin' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $user->refresh();

        $this->assertEquals('user@example.com', $user->email);
    }

    public function test_it_requires_name()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create();

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/manage-user/'.$user->id, [
            'email' => 'updated@example.com',
            'is_admin' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_it_requires_email()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create();

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/manage-user/'.$user->id, [
            'name' => 'Updated Name',
            'is_admin' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_requires_is_admin()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create();

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/manage-user/'.$user->id, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_admin']);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->postJson('/api/manage-user/1', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_admin' => 0,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
