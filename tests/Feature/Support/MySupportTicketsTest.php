<?php

namespace Tests\Feature\Support;

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

class MySupportTicketsTest extends TestCase
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

    public function test_it_lists_my_support_tickets()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $ticket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'I need help with dividend data.',
        ]);

        TicketResponse::factory()->count(2)->create([
            'support_ticket_id' => $ticket->id,
        ]);

        $response = $this->getJson('/api/my-support-tickets?status=all');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('tickets.data.0.id', $ticket->id)
            ->assertJsonPath('tickets.data.0.issue', 'I need help with dividend data.')
            ->assertJsonPath('tickets.data.0.response_count', 2);
    }

    public function test_it_filters_open_support_tickets()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

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

        $response = $this->getJson('/api/my-support-tickets?status=open');

        $response->assertOk()
            ->assertJsonPath('tickets.data.0.id', $openTicket->id)
            ->assertJsonCount(1, 'tickets.data');
    }

    public function test_it_requires_status()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/my-support-tickets');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/my-support-tickets?status=all');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
