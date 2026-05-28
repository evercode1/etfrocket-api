<?php

namespace Tests\Feature\Support\Admin;

use App\Models\Status;
use App\Models\SupportTicket;
use App\Models\SupportTopic;
use App\Models\User;
use Database\Seeders\StatusSeeder;
use Database\Seeders\SupportTopicSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetSupportTicketsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ticket_responses')->truncate();
        DB::table('support_tickets')->truncate();
        DB::table('support_topics')->truncate();
        DB::table('statuses')->truncate();
        DB::table('users')->truncate();

        $this->seed(StatusSeeder::class);
        $this->seed(SupportTopicSeeder::class);
    }

    protected function tearDown(): void
    {
        DB::table('ticket_responses')->truncate();
        DB::table('support_tickets')->truncate();
        DB::table('support_topics')->truncate();
        DB::table('statuses')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_admin_can_get_all_support_tickets()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
            'name' => 'Admin User',
        ]);

        $user = User::factory()->create([
            'name' => 'Ticket User',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $topic = SupportTopic::first();

        $ticket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'This is a long support ticket message for testing.',
        ]);

        $response = $this->getJson('/api/get-support-tickets?status=1');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('tickets.data.0.id', $ticket->id)
            ->assertJsonPath('tickets.data.0.support_topic_name', $topic->support_topic_name)
            ->assertJsonPath('tickets.data.0.status_name', 'Open')
            ->assertJsonPath('tickets.data.0.name', 'Ticket User')
            ->assertJsonPath('tickets.data.0.ticket_text', substr('This is a long support ticket message for testing.', 0, 24))
            ->assertJsonPath('tickets.data.0.details_endpoint', 'support-ticket/'.$ticket->id);
    }

    public function test_admin_can_filter_closed_support_tickets()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create();

        Sanctum::actingAs($admin, ['*']);

        $topic = SupportTopic::first();

        $closedTicket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::CLOSED,
            'ticket_text' => 'Closed ticket',
        ]);

        SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'Open ticket',
        ]);

        $response = $this->getJson('/api/get-support-tickets?status=2');

        $response->assertOk()
            ->assertJsonPath('tickets.data.0.id', $closedTicket->id)
            ->assertJsonPath('tickets.data.0.status_name', 'Closed')
            ->assertJsonCount(1, 'tickets.data');
    }

    public function test_admin_can_filter_open_support_tickets()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create();

        Sanctum::actingAs($admin, ['*']);

        $topic = SupportTopic::first();

        $openTicket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'Open ticket',
        ]);

        SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::CLOSED,
            'ticket_text' => 'Closed ticket',
        ]);

        $response = $this->getJson('/api/get-support-tickets?status=3');

        $response->assertOk()
            ->assertJsonPath('tickets.data.0.id', $openTicket->id)
            ->assertJsonPath('tickets.data.0.status_name', 'Open')
            ->assertJsonCount(1, 'tickets.data');
    }

    public function test_it_requires_status()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/get-support-tickets');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_status_must_be_integer()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/get-support-tickets?status=open');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/get-support-tickets?status=1');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
