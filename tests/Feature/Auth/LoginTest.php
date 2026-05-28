<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('users')->truncate();
        DB::table('user_verifications')->truncate();

        Mail::fake();
    }

    protected function tearDown(): void
    {
        DB::table('users')->truncate();
        DB::table('user_verifications')->truncate();

        parent::tearDown();
    }

    public function test_successful_login_returns_token_and_user_data()
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'password' => 'secret123',
            'email_verified_at' => now(),

        ]);

        $payload = [

            'email' => $user->email,
            'password' => 'secret123',

        ];

        $response = $this->postJson('/api/login', $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure([

            'user',

        ]);
    }

    public function test_login_fails_with_wrong_password()
    {
        $user = User::factory()->create([

            'email' => 'wrongpass@example.com',
            'password' => 'correctpass',
            'email_verified_at' => now(),

        ]);

        $payload = [

            'email' => $user->email,
            'password' => 'wrongpass',

        ];

        $response = $this->postJson('/api/login', $payload);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Bad credentials',
        ]);
    }

    public function test_login_returns_verification_message_if_email_not_verified()
    {
        $user = User::factory()->create([

            'email' => 'unverified@example.com',
            'password' => 'needverify',
            'email_verified_at' => null,

        ]);

        $payload = [
            'email' => $user->email,
            'password' => 'needverify',

        ];

        $response = $this->postJson('/api/login', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Please verify your account.',
        ]);

        Mail::assertSent(VerifyEmail::class);

        $this->assertDatabaseHas('user_verifications', [

            'user_id' => $user->id,

        ]);
    }
}
