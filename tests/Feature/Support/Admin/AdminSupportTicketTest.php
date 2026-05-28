<?php

namespace Tests\Feature\Support\Admin;

use App\Models\Status;
use App\Models\SupportTicket;
use App\Models\SupportTopic;
use App\Models\TicketResponse;
use App\Models\User;
use Database\Seeders\StatusSeeder;
use Database\Seeders\SupportTopicSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminSupportTicketTest extends TestCase
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

    public function test_it_shows_support_ticket()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
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
            'ticket_text' => 'I need help with ETF dividend data.',
        ]);

        $ticketResponse = TicketResponse::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $ticket->id,
            'response_text' => 'Customer response text.',
            'is_from_customer' => 1,
            'is_read' => 1,
        ]);

        $response = $this->getJson('/api/support-ticket/'.$ticket->id);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('ticket.id', $ticket->id)
            ->assertJsonPath('ticket.support_topic_name', $topic->support_topic_name)
            ->assertJsonPath('ticket.status_name', 'Open')
            ->assertJsonPath('ticket.name', 'Ticket User')
            ->assertJsonPath('ticket.ticket_text', 'I need help with ETF dividend data.')
            ->assertJsonPath('ticket.user_id', $user->id)
            ->assertJsonPath('ticket.status_id', Status::OPEN)
            ->assertJsonPath('ticket.support_topic_id', $topic->id)
            ->assertJsonPath('ticket.ticket_responses.0.id', $ticketResponse->id)
            ->assertJsonPath('ticket.ticket_responses.0.response_text', 'Customer response text.');
    }

    public function test_it_returns_null_ticket_when_ticket_does_not_exist()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/support-ticket/999');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'ticket' => null,
            ]);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/support-ticket/1');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
