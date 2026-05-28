<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserVerification;
use Carbon\Carbon;
use Tests\TestCase;

class VerifyAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-03 12:00:00'));

        UserVerification::truncate();
        User::truncate();

    }

    protected function tearDown(): void
    {

        UserVerification::truncate();
        User::truncate();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_message_when_token_is_not_found(): void
    {
        $response = $this->getJson('/api/account/verify/invalid-token');

        $response->assertOk()
            ->assertJson([
                'message' => 'Token not found, try requesting the token again.',
            ]);
    }

    public function test_it_verifies_an_inactive_user_and_deletes_the_token(): void
    {
        $user = User::factory()->create([
            'is_active' => 0,
            'email_verified_at' => null,
        ]);

        $verification = UserVerification::factory()->create([
            'user_id' => $user->id,
            'token' => 'valid-verification-token',
        ]);

        $response = $this->getJson('/api/account/verify/'.$verification->token);

        $response->assertOk()
            ->assertJson([
                'message' => 'Your e-mail is verified. You may now login.',
            ]);

        $user->refresh();
        $this->assertTrue($user->is_active == 1);
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals(
            Carbon::now()->toDateTimeString(),
            Carbon::parse($user->email_verified_at)->toDateTimeString()
        );

        $this->assertDatabaseMissing('user_verifications', [
            'token' => $verification->token,
        ]);
    }

    public function test_it_returns_already_verified_message_for_active_user_and_deletes_token(): void
    {
        $user = User::factory()->create([
            'is_active' => 1,
            'email_verified_at' => Carbon::now(),
        ]);

        $verification = UserVerification::factory()->create([
            'user_id' => $user->id,
            'token' => 'already-active-token',
        ]);

        $response = $this->getJson('/api/account/verify/'.$verification->token);

        $response->assertOk()
            ->assertJson([
                'message' => 'Your e-mail is already verified. You may now login.',
            ]);

        $this->assertDatabaseMissing('user_verifications', [
            'token' => $verification->token,
        ]);
    }

    public function test_it_returns_user_not_found_when_verification_record_has_no_valid_user(): void
    {
        $verification = UserVerification::factory()->create([
            'user_id' => 999999,
            'token' => 'orphaned-token',
        ]);

        $response = $this->getJson('/api/account/verify/'.$verification->token);

        $response->assertOk()
            ->assertJson([
                'message' => 'User not found',
            ]);
    }
}
