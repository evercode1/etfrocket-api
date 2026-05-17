<?php

namespace Tests\Feature\Support;

use App\Models\Status;
use App\Models\SupportTicket;
use App\Models\SupportTopic;
use App\Models\TicketResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UnreadSupportResponsesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ticket_responses')->truncate();
        DB::table('support_tickets')->truncate();
        DB::table('support_topics')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('ticket_responses')->truncate();
        DB::table('support_tickets')->truncate();
        DB::table('support_topics')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_get_unread_support_responses(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::factory()->create([
            'support_topic_name' => 'Account Access',
        ]);

        $ticket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'I cannot access my account.',
        ]);

        $supportResponse = TicketResponse::factory()->create([
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'response_text' => 'We checked your account and reset access.',
            'is_from_customer' => 0,
            'is_read' => 0,
            'created_at' => now(),
        ]);

        TicketResponse::factory()->create([
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'response_text' => 'This response has already been read.',
            'is_from_customer' => 0,
            'is_read' => 1,
        ]);

        TicketResponse::factory()->create([
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'response_text' => 'This is the user replying.',
            'is_from_customer' => 1,
            'is_read' => 0,
        ]);

        $response = $this->getJson('/api/unread-support-responses');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('unread_support_responses_count', 1)
            ->assertJsonCount(1, 'tickets')
            ->assertJsonPath('tickets.0.topic', 'Account Access')
            ->assertJsonPath('tickets.0.latest_response_id', $supportResponse->id)
            ->assertJsonPath('tickets.0.ticket_id', $ticket->id)
            ->assertJsonPath(
                'tickets.0.message_preview',
                'We checked your account and reset access.'
            )
            ->assertJsonPath(
                'tickets.0.ticket_issue',
                'I cannot access my account.'
            );
    }

    public function test_it_only_returns_unread_support_responses_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::factory()->create([
            'support_topic_name' => 'Portfolio Help',
        ]);

        $userTicket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'My portfolio is missing.',
        ]);

        $otherUserTicket = SupportTicket::factory()->create([
            'user_id' => $otherUser->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'Other user ticket.',
        ]);

        TicketResponse::factory()->create([
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $userTicket->id,
            'user_id' => $user->id,
            'response_text' => 'Response for authenticated user.',
            'is_from_customer' => 0,
            'is_read' => 0,
        ]);

        TicketResponse::factory()->create([
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $otherUserTicket->id,
            'user_id' => $otherUser->id,
            'response_text' => 'Response for another user.',
            'is_from_customer' => 0,
            'is_read' => 0,
        ]);

        $response = $this->getJson('/api/unread-support-responses');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('unread_support_responses_count', 1)
            ->assertJsonCount(1, 'tickets')
            ->assertJsonPath('tickets.0.ticket_id', $userTicket->id)
            ->assertJsonPath(
                'tickets.0.message_preview',
                'Response for authenticated user.'
            );
    }

    public function test_it_returns_zero_when_user_has_no_unread_support_responses(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/unread-support-responses');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('unread_support_responses_count', 0)
            ->assertJsonCount(0, 'tickets');
    }

    public function test_guest_cannot_get_unread_support_responses(): void
    {
        $response = $this->getJson('/api/unread-support-responses');

        $response->assertUnauthorized();
    }
}
