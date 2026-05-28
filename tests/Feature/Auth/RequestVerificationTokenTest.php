<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmail;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequestVerificationTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_verifications')->truncate();
        DB::table('users')->truncate();

        Mail::fake();
    }

    protected function tearDown(): void
    {
        DB::table('user_verifications')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_message_when_email_is_not_found()
    {
        $response = $this->getJson('/api/request-verification-token?email=missing@example.com');

        $response->assertOk()
            ->assertJson([
                'message' => 'your email was not found in our system',
            ]);

        Mail::assertNothingSent();
    }

    public function test_it_returns_message_when_user_is_already_verified()
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->getJson('/api/request-verification-token?email='.$user->email);

        $response->assertOk()
            ->assertJson([
                'message' => 'You are already verified',
            ]);

        $this->assertDatabaseMissing('user_verifications', [
            'user_id' => $user->id,
        ]);

        Mail::assertNothingSent();
    }

    public function test_it_creates_verification_token_and_sends_email()
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->getJson('/api/request-verification-token?email='.$user->email);

        $response->assertOk()
            ->assertJson([
                'message' => 'email has been sent',
            ]);

        $this->assertDatabaseHas('user_verifications', [
            'user_id' => $user->id,
        ]);

        $verification = UserVerification::where('user_id', $user->id)->first();

        $this->assertNotNull($verification);
        $this->assertEquals(6, strlen($verification->token));

        Mail::assertSent(VerifyEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_it_deletes_existing_token_before_creating_new_one()
    {
        $user = User::factory()->create([
            'email' => 'replacement@example.com',
            'email_verified_at' => null,
        ]);

        $oldVerification = UserVerification::factory()->create([
            'user_id' => $user->id,
            'token' => '111111',
        ]);

        $response = $this->getJson('/api/request-verification-token?email='.$user->email);

        $response->assertOk()
            ->assertJson([
                'message' => 'email has been sent',
            ]);

        $this->assertDatabaseMissing('user_verifications', [
            'user_id' => $user->id,
            'token' => '111111',
        ]);

        $this->assertEquals(1, UserVerification::where('user_id', $user->id)->count());

        $newVerification = UserVerification::where('user_id', $user->id)->first();

        $this->assertNotEquals('111111', $newVerification->token);
        $this->assertEquals(6, strlen($newVerification->token));

        Mail::assertSent(VerifyEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}
