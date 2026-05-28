<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Database\Seeders\SupportTopicSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NewSupportTicketFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('support_topics')->truncate();
        DB::table('users')->truncate();

        $this->seed(SupportTopicSeeder::class);
    }

    protected function tearDown(): void
    {
        DB::table('support_topics')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_new_support_ticket_form_config()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/new-support-ticket-form');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'form_config' => [
                    [
                        'name' => 'support_topic_id',
                        'type' => 'select',
                        'label' => 'Support Topic',
                        'required' => 1,
                        'max_length' => 50,
                        'instructions' => '',
                    ],
                    [
                        'name' => 'ticket_text',
                        'type' => 'text',
                        'label' => 'Your Issue',
                        'required' => 1,
                        'max_length' => 2000,
                        'instructions' => '',
                    ],
                ],
                'post_endpoint' => 'create-support-ticket',
            ]);

        $response->assertJsonPath('form_config.0.options.1', 'Account Access');
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/new-support-ticket-form');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
