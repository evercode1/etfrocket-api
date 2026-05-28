<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EditMyEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_edit_email_form_config()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/edit-my-email');

        $response->assertOk()
            ->assertJson([
                'form_config' => [
                    [
                        'name' => 'email',
                        'type' => 'email',
                        'label' => 'Email Address',
                        'required' => 1,
                        'max_length' => 50,
                        'instructions' => '',
                    ],
                    [
                        'name' => 'email_confirmation',
                        'type' => 'email',
                        'label' => 'Confirm Email Address',
                        'required' => 1,
                        'max_length' => 50,
                        'instructions' => '',
                    ],
                ],
                'post_endpoint' => 'update-my-email',
            ]);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/edit-my-email');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
