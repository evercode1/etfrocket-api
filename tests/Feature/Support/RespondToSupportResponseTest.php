<?php

namespace Tests\Feature\Support;

use App\Models\Status;
use App\Models\SupportTicket;
use App\Models\SupportTopic;
use App\Models\User;
use Database\Seeders\StatusSeeder;
use Database\Seeders\SupportTopicSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RespondToSupportResponseTest extends TestCase
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

    public function test_it_creates_response_to_support_ticket()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $ticket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'ETF dividend issue',
        ]);

        $response = $this->postJson('/api/respond-to-support-response', [
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $ticket->id,
            'response_text' => 'Thank you for the update.',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Response added successfully.',
            ])
            ->assertJsonPath('ticket_response.user_id', $user->id)
            ->assertJsonPath('ticket_response.support_ticket_id', $ticket->id)
            ->assertJsonPath('ticket_response.response_text', 'Thank you for the update.')
            ->assertJsonPath('ticket_response.is_from_customer', 1)
            ->assertJsonPath('ticket_response.is_read', 1);

        $this->assertDatabaseHas('ticket_responses', [
            'user_id' => $user->id,
            'support_ticket_id' => $ticket->id,
            'response_text' => 'Thank you for the update.',
            'is_from_customer' => 1,
            'is_read' => 1,
        ]);
    }

    public function test_it_requires_support_topic_id()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $ticket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::OPEN,
        ]);

        $response = $this->postJson('/api/respond-to-support-response', [
            'support_ticket_id' => $ticket->id,
            'response_text' => 'Thank you for the update.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['support_topic_id']);
    }

    public function test_it_requires_support_ticket_id()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $response = $this->postJson('/api/respond-to-support-response', [
            'support_topic_id' => $topic->id,
            'response_text' => 'Thank you for the update.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['support_ticket_id']);
    }

    public function test_it_requires_response_text()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $ticket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::OPEN,
        ]);

        $response = $this->postJson('/api/respond-to-support-response', [
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $ticket->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['response_text']);
    }

    public function test_response_text_must_not_exceed_1000_characters()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $ticket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::OPEN,
        ]);

        $response = $this->postJson('/api/respond-to-support-response', [
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $ticket->id,
            'response_text' => str_repeat('a', 1001),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['response_text']);
    }

    public function test_it_rejects_ticket_that_does_not_belong_to_user()
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $otherTicket = SupportTicket::factory()->create([
            'user_id' => $otherUser->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
        ]);

        $response = $this->postJson('/api/respond-to-support-response', [
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $otherTicket->id,
            'response_text' => 'Unauthorized response',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['support_ticket_id']);

        $this->assertDatabaseMissing('ticket_responses', [
            'response_text' => 'Unauthorized response',
        ]);
    }

    public function test_it_rejects_closed_ticket()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $closedTicket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::CLOSED,
        ]);

        $response = $this->postJson('/api/respond-to-support-response', [
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $closedTicket->id,
            'response_text' => 'Trying to respond to closed ticket',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['support_ticket_id']);

        $this->assertDatabaseMissing('ticket_responses', [
            'response_text' => 'Trying to respond to closed ticket',
        ]);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->postJson('/api/respond-to-support-response', [
            'support_topic_id' => 1,
            'support_ticket_id' => 1,
            'response_text' => 'Unauthorized',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
