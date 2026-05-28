<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogoutTest extends TestCase
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

    public function test_it_logs_out_authenticated_user_and_deletes_tokens()
    {
        $user = User::factory()->create();

        $user->createToken('test-token-one');
        $user->createToken('test-token-two');

        $this->assertEquals(
            2,
            PersonalAccessToken::where('tokenable_id', $user->id)->count()
        );

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/logout');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Logged out',
            ]);

        $this->assertEquals(
            0,
            PersonalAccessToken::where('tokenable_id', $user->id)->count()
        );
    }

    public function test_it_requires_authentication()
    {
        $response = $this->postJson('/api/logout');

        $response->assertUnauthorized();
    }
}
