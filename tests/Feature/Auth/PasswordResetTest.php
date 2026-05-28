<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('password_reset_tokens')
            ->truncate();

        DB::table('users')
            ->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('password_reset_tokens')
            ->truncate();

        DB::table('users')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_resets_password_and_deletes_token()
    {
        $user = User::factory()->create([

            'email' => 'reset@example.com',

            'password' => Hash::make(
                'old-password'
            ),

        ]);

        PasswordResetToken::create([

            'email' => $user->email,

            'token' => 'valid-token',

            'created_at' => now(),

        ]);

        $response = $this->postJson(
            '/api/password-reset',
            [

                'email' => $user->email,

                'token' => 'valid-token',

                'password' => 'new-password',

                'password_confirmation' => 'new-password',

            ]
        );

        $response->assertStatus(201)

            ->assertJson([

                'message' => 'Your Password has been updated',

            ]);

        $user->refresh();

        $this->assertTrue(

            Hash::check(

                'new-password',

                $user->password

            )

        );

        $this->assertDatabaseMissing(
            'password_reset_tokens',
            [

                'email' => $user->email,

                'token' => 'valid-token',

            ]
        );
    }

    public function test_it_returns_error_when_token_email_does_not_match_user_email()
    {
        $user = User::factory()->create([

            'email' => 'user@example.com',

            'password' => Hash::make(
                'old-password'
            ),

        ]);

        PasswordResetToken::create([

            'email' => 'different@example.com',

            'token' => 'valid-token',

            'created_at' => now(),

        ]);

        $response = $this->postJson(
            '/api/password-reset',
            [

                'email' => $user->email,

                'token' => 'valid-token',

                'password' => 'new-password',

                'password_confirmation' => 'new-password',

            ]
        );

        $response->assertStatus(401)

            ->assertJson([

                'status' => 'error',

                'message' => 'invalid credentials',

            ]);

        $user->refresh();

        $this->assertFalse(

            Hash::check(

                'new-password',

                $user->password

            )

        );

        $this->assertDatabaseHas(
            'password_reset_tokens',
            [

                'email' => 'different@example.com',

                'token' => 'valid-token',

            ]
        );
    }

    public function test_it_requires_password_email_and_token()
    {
        $response = $this->postJson(
            '/api/password-reset',
            []
        );

        $response->assertStatus(422)

            ->assertJsonValidationErrors([

                'password',

                'email',

                'token',

            ]);
    }

    public function test_password_must_be_confirmed()
    {
        $response = $this->postJson(
            '/api/password-reset',
            [

                'email' => 'test@example.com',

                'token' => 'valid-token',

                'password' => 'new-password',

                'password_confirmation' => 'different-password',

            ]
        );

        $response->assertStatus(422)

            ->assertJsonValidationErrors([

                'password',

            ]);
    }
}
