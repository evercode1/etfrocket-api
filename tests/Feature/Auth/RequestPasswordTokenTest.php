<?php

namespace Tests\Feature\Auth;

use App\Mail\ForgotPasswordEmail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequestPasswordTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('password_reset_tokens')->truncate();
        DB::table('users')->truncate();

        Mail::fake();
    }

    protected function tearDown(): void
    {
        DB::table('password_reset_tokens')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_error_when_email_is_not_found()
    {
        $response = $this->postJson('/api/request-password-token', [
            'email' => 'missing@example.com',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'your email was not found in our system',
            ]);

        Mail::assertNothingSent();
    }

    public function test_it_creates_password_reset_token_and_sends_email()
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $response = $this->postJson('/api/request-password-token', [
            'email' => $user->email,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'email has been sent',
            ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        $token = PasswordResetToken::where('email', $user->email)->first();

        $this->assertNotNull($token);
        $this->assertEquals(64, strlen($token->token));

        Mail::assertSent(ForgotPasswordEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_it_deletes_existing_token_before_creating_new_one()
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        PasswordResetToken::create([
            'email' => $user->email,
            'token' => 'old-token',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/request-password-token', [
            'email' => $user->email,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'email has been sent',
            ]);

        $this->assertEquals(
            1,
            PasswordResetToken::where('email', $user->email)->count()
        );

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
            'token' => 'old-token',
        ]);

        $newToken = PasswordResetToken::where('email', $user->email)->first();

        $this->assertNotEquals('old-token', $newToken->token);
        $this->assertEquals(64, strlen($newToken->token));

        Mail::assertSent(ForgotPasswordEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_it_requires_email()
    {
        $response = $this->postJson('/api/request-password-token', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_must_be_valid_email()
    {
        $response = $this->postJson('/api/request-password-token', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
