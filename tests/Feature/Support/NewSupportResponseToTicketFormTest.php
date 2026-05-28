<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NewSupportResponseToTicketFormTest extends TestCase
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

    public function test_it_returns_new_support_response_form_config()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/new-support-response-to-ticket-form');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'form_config' => [
                    [
                        'name' => 'response_text',
                        'type' => 'text',
                        'label' => 'Your Response',
                        'required' => 1,
                        'max_length' => 2000,
                        'instructions' => '',
                    ],
                ],
                'post_endpoint' => 'respond-to-support-response',
            ]);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/new-support-response-to-ticket-form');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
