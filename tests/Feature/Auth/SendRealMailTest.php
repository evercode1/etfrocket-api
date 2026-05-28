<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserVerification;
use App\Services\Auth\RegistrationTransactionService;
use Carbon\Carbon;
use Tests\TestCase;

class SendRealMailTest extends TestCase
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

    /**
     * This test will send an ACTUAL email to Mailtrap.
     * Note: We are NOT calling Mail::fake() here.
     */
    public function test_it_sends_a_real_email_to_mailtrap(): void
    {
        $payload = (object) [
            'name' => 'Mailtrap Test User',
            'email' => 'yo@yo.com', // Mailtrap will catch any address
            'password' => 'password123',
        ];

        // Call the service directly
        $user = app(RegistrationTransactionService::class)->createUser($payload);

        // Assert the user was created
        $this->assertDatabaseHas('users', ['email' => 'yo@yo.com']);

        // At this point, check your Mailtrap.io inbox!
    }
}
