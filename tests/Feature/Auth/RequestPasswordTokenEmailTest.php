<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RequestPasswordTokenEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('password_reset_tokens')->truncate();
        DB::table('users')->truncate();

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => env('MAIL_HOST'),
            'mail.mailers.smtp.port' => env('MAIL_PORT'),
            'mail.mailers.smtp.username' => env('MAIL_USERNAME'),
            'mail.mailers.smtp.password' => env('MAIL_PASSWORD'),
            'mail.mailers.smtp.encryption' => env('MAIL_ENCRYPTION'),
            'mail.from.address' => env('MAIL_FROM_ADDRESS'),
            'mail.from.name' => env('MAIL_FROM_NAME'),
        ]);
    }

    protected function tearDown(): void
    {
        DB::table('password_reset_tokens')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_sends_actual_password_reset_email_to_mailtrap()
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
    }
}
