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

class MySupportResponseTest extends TestCase
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

    public function test_it_shows_my_support_response_and_marks_it_as_read()
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

        $ticketResponse = TicketResponse::factory()->create([
            'user_id' => $user->id,
            'support_ticket_id' => $ticket->id,
            'support_topic_id' => $topic->id,
            'response_text' => 'Thanks, we are looking into this.',
            'is_read' => 0,
        ]);

        $response = $this->getJson('/api/my-support-response?ticket_response_id='.$ticketResponse->id);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Response retrieved successfully.',
            ])
            ->assertJsonPath('ticket_response.id', $ticketResponse->id)
            ->assertJsonPath('ticket_response.response_text', 'Thanks, we are looking into this.')
            ->assertJsonPath('ticket_response.is_read', 1);

        $ticketResponse->refresh();

        $this->assertEquals(1, $ticketResponse->is_read);
    }

    public function test_it_requires_ticket_response_id()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/my-support-response');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ticket_response_id']);
    }

    public function test_ticket_response_id_must_be_integer()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/my-support-response?ticket_response_id=abc');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ticket_response_id']);
    }

    public function test_it_rejects_response_that_does_not_belong_to_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $otherTicket = SupportTicket::factory()->create([
            'user_id' => $otherUser->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'Other user ticket',
        ]);

        $otherResponse = TicketResponse::factory()->create([
            'user_id' => $otherUser->id,
            'support_ticket_id' => $otherTicket->id,
            'support_topic_id' => $topic->id,
            'response_text' => 'Other user response',
            'is_read' => 0,
        ]);

        $response = $this->getJson('/api/my-support-response?ticket_response_id='.$otherResponse->id);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ticket_response_id']);

        $otherResponse->refresh();

        $this->assertEquals(0, $otherResponse->is_read);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/my-support-response?ticket_response_id=1');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
