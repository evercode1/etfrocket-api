<?php

namespace Tests\Feature\ManageUsers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteUserTest extends TestCase
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

    public function test_admin_can_delete_user()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'Delete Me',
            'email' => 'delete@example.com',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->deleteJson('/api/delete-user/'.$user->id);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'The user has been deleted.',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_it_returns_success_even_when_user_does_not_exist()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->deleteJson('/api/delete-user/999');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'The user has been deleted.',
            ]);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->deleteJson('/api/delete-user/1');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
