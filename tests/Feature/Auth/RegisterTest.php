<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear relevant tables

        DB::table('users')->truncate();
        DB::table('user_verifications')->truncate();

        Mail::fake(); // Prevent actual emails
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        DB::table('users')->truncate();
        DB::table('user_verifications')->truncate();

        parent::tearDown();
    }

    public function test_successful_registration()
    {

        $payload = [
            'name' => 'john_test',
            'email' => 'john@example.com',
            'password' => 'securePassword123',
            'password_confirmation' => 'securePassword123',

        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'please confirm your email',
        ]);

        $user = User::where('email', $payload['email'])->first();

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
        ]);

        $this->assertDatabaseHas('user_verifications', [
            'user_id' => $user->id,
        ]);

        Mail::assertSent(VerifyEmail::class);
    }

    public function test_registration_fails_with_missing_required_fields()
    {
        $response = $this->postJson('/api/register', []); // no payload

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }
}
