<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoginCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('personal_access_tokens')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('personal_access_tokens')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_false_when_token_does_not_exist()
    {
        $response = $this->getJson('/api/login-check?token=missing-token');

        $response->assertOk()
            ->assertJson([
                'exists' => false,
                'user' => null,
            ]);
    }

    public function test_it_returns_true_and_user_when_token_exists()
    {
        $user = User::factory()->create([
            'email' => 'logincheck@example.com',
        ]);

        $newAccessToken = $user->createToken('test-token');

        $plainTextToken = explode('|', $newAccessToken->plainTextToken, 2)[1];

        $response = $this->getJson('/api/login-check?token='.$plainTextToken);

        $response->assertOk()
            ->assertJson([
                'exists' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => 'logincheck@example.com',
                ],
            ]);
    }

    public function test_it_requires_token()
    {
        $response = $this->getJson('/api/login-check');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }
}
