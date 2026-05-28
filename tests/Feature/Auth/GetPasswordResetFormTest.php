<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GetPasswordResetFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('password_reset_tokens')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('password_reset_tokens')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_error_when_token_is_not_found()
    {
        $response = $this->getJson('/api/get-password-reset-form/invalid-token');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Something is wrong with your request',
            ]);
    }

    public function test_it_returns_error_when_user_is_not_found()
    {
        PasswordResetToken::create([
            'email' => 'missing@example.com',
            'token' => 'valid-token',
        ]);

        $response = $this->getJson('/api/get-password-reset-form/valid-token');

        $response->assertOk()
            ->assertJson([
                'status' => 'error',
                'message' => 'Sorry, we could not find your account.',
            ]);
    }

    public function test_it_returns_user_and_token_when_password_reset_exists()
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        PasswordResetToken::create([
            'email' => $user->email,
            'token' => 'valid-token',
        ]);

        $response = $this->getJson('/api/get-password-reset-form/valid-token');

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User found',
                'user_id' => $user->id,
                'token' => 'valid-token',
            ]);
    }
}
