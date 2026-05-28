<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateMyUserNameTest extends TestCase
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

    public function test_it_updates_authenticated_users_username()
    {
        $user = User::factory()->create([
            'name' => 'OldName',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/update-my-user-name', [
            'name' => 'NewName',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'your settings have been updated.',
            ]);

        $user->refresh();

        $this->assertEquals('NewName', $user->name);
    }

    public function test_it_requires_name()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/update-my-user-name', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_it_requires_unique_username()
    {
        $user = User::factory()->create([
            'name' => 'OriginalName',
        ]);

        User::factory()->create([
            'name' => 'TakenName',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/update-my-user-name', [
            'name' => 'TakenName',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $user->refresh();

        $this->assertEquals('OriginalName', $user->name);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->postJson('/api/update-my-user-name', [
            'name' => 'NewName',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
