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

class MySupportTicketTest extends TestCase
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

    public function test_it_shows_my_support_ticket()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $ticket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'I need help with ETF dividend data.',
        ]);

        TicketResponse::factory()->create([
            'support_ticket_id' => $ticket->id,
            'response_text' => 'Thanks, we are looking into this.',
        ]);

        $response = $this->getJson('/api/my-support-ticket/'.$ticket->id);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('data.id', $ticket->id)
            ->assertJsonPath('data.issue', 'I need help with ETF dividend data.')
            ->assertJsonPath('data.topic', 'Account Access')
            ->assertJsonPath('data.status_name', 'Open')
            ->assertJsonPath('data.ticket_responses.0.response_text', 'Thanks, we are looking into this.');
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/my-support-ticket/1');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
