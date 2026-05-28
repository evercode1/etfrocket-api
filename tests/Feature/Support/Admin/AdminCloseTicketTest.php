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

class AdminCloseTicketTest extends TestCase
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

    public function test_admin_can_close_ticket()
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

        $response = $this->postJson('/api/close-ticket', [
            'id' => $ticket->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'You have closed ticket# '.$ticket->id,
            ]);

        $ticket->refresh();

        $this->assertEquals(Status::CLOSED, $ticket->status_id);

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status_id' => Status::CLOSED,
        ]);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->postJson('/api/close-ticket', [
            'id' => 1,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
