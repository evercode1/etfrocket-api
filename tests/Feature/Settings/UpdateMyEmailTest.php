<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateMyEmailTest extends TestCase
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

    public function test_it_updates_authenticated_users_email()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/update-my-email', [
            'email' => 'new@example.com',
            'email_confirmation' => 'new@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'your email has been updated.',
            ]);

        $user->refresh();

        $this->assertEquals('new@example.com', $user->email);
    }

    public function test_it_returns_error_when_email_confirmation_does_not_match()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/update-my-email', [
            'email' => 'new@example.com',
            'email_confirmation' => 'different@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'email and email confirmation do not match.',
            ]);

        $user->refresh();

        $this->assertEquals('old@example.com', $user->email);
    }

    public function test_it_requires_valid_email()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/update-my-email', [
            'email' => 'not-an-email',
            'email_confirmation' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_requires_unique_email()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/update-my-email', [
            'email' => 'taken@example.com',
            'email_confirmation' => 'taken@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $user->refresh();

        $this->assertEquals('old@example.com', $user->email);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->postJson('/api/update-my-email', [
            'email' => 'new@example.com',
            'email_confirmation' => 'new@example.com',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
