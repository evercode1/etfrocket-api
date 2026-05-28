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

class SupportReplyToTicketTest extends TestCase
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

    public function test_admin_can_reply_to_support_ticket()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create();

        Sanctum::actingAs($admin, ['*']);

        $topic = SupportTopic::first();

        $ticket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'ETF dividend issue',
        ]);

        $response = $this->postJson('/api/support-reply-to-ticket', [
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'response_text' => 'We are reviewing your issue.',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Response added to ticket successfully',
            ])
            ->assertJsonPath('response.support_ticket_id', $ticket->id)
            ->assertJsonPath('response.support_topic_id', $topic->id)
            ->assertJsonPath('response.user_id', $user->id)
            ->assertJsonPath('response.response_text', 'We are reviewing your issue.')
            ->assertJsonPath('response.is_from_customer', 0)
            ->assertJsonPath('response.is_read', 0);

        $this->assertDatabaseHas('ticket_responses', [
            'support_ticket_id' => $ticket->id,
            'support_topic_id' => $topic->id,
            'user_id' => $user->id,
            'response_text' => 'We are reviewing your issue.',
            'is_from_customer' => 0,
            'is_read' => 0,
        ]);
    }

    public function test_admin_can_reply_and_close_ticket()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create();

        Sanctum::actingAs($admin, ['*']);

        $topic = SupportTopic::first();

        $ticket = SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'ETF dividend issue',
        ]);

        $response = $this->postJson('/api/support-reply-to-ticket', [
            'support_topic_id' => $topic->id,
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'response_text' => 'Issue resolved.',
            'status' => 'close',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Response added to ticket successfully',
            ]);

        $ticket->refresh();

        $this->assertEquals(Status::CLOSED, $ticket->status_id);
    }

    public function test_it_requires_support_topic_id()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/support-reply-to-ticket', [
            'support_ticket_id' => 1,
            'user_id' => 1,
            'response_text' => 'Response text',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['support_topic_id']);
    }

    public function test_it_requires_support_ticket_id()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $topic = SupportTopic::first();

        $response = $this->postJson('/api/support-reply-to-ticket', [
            'support_topic_id' => $topic->id,
            'user_id' => 1,
            'response_text' => 'Response text',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['support_ticket_id']);
    }

    public function test_it_requires_user_id()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $topic = SupportTopic::first();

        $response = $this->postJson('/api/support-reply-to-ticket', [
            'support_topic_id' => $topic->id,
            'support_ticket_id' => 1,
            'response_text' => 'Response text',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_it_requires_response_text()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $topic = SupportTopic::first();

        $response = $this->postJson('/api/support-reply-to-ticket', [
            'support_topic_id' => $topic->id,
            'support_ticket_id' => 1,
            'user_id' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['response_text']);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->postJson('/api/support-reply-to-ticket', [
            'support_topic_id' => 1,
            'support_ticket_id' => 1,
            'user_id' => 1,
            'response_text' => 'Unauthorized',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
