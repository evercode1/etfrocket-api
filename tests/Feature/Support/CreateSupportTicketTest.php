<?php

namespace Tests\Feature\Support;

use App\Models\Status;
use App\Models\SupportTopic;
use App\Models\User;
use Database\Seeders\StatusSeeder;
use Database\Seeders\SupportTopicSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateSupportTicketTest extends TestCase
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

    public function test_it_creates_support_ticket()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $response = $this->postJson('/api/create-support-ticket', [
            'support_topic_id' => $topic->id,
            'ticket_text' => 'I need help with ETF dividend data.',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Support ticket created successfully.',
            ])
            ->assertJsonPath('support_ticket.user_id', $user->id)
            ->assertJsonPath('support_ticket.support_topic_id', $topic->id)
            ->assertJsonPath('support_ticket.ticket_text', 'I need help with ETF dividend data.');

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'I need help with ETF dividend data.',
        ]);
    }

    public function test_it_requires_support_topic_id()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/create-support-ticket', [
            'ticket_text' => 'I need help with ETF dividend data.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['support_topic_id']);
    }

    public function test_support_topic_id_must_be_integer()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/create-support-ticket', [
            'support_topic_id' => 'abc',
            'ticket_text' => 'I need help with ETF dividend data.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['support_topic_id']);
    }

    public function test_it_requires_ticket_text()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $response = $this->postJson('/api/create-support-ticket', [
            'support_topic_id' => $topic->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ticket_text']);
    }

    public function test_ticket_text_must_not_exceed_1000_characters()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $topic = SupportTopic::first();

        $response = $this->postJson('/api/create-support-ticket', [
            'support_topic_id' => $topic->id,
            'ticket_text' => str_repeat('a', 1001),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ticket_text']);
    }

    public function test_it_requires_authentication()
    {
        $topic = SupportTopic::first();

        $response = $this->postJson('/api/create-support-ticket', [
            'support_topic_id' => $topic->id,
            'ticket_text' => 'I need help with ETF dividend data.',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
