<?php

namespace Tests\Feature\Support\Admin;

use App\Models\Status;
use App\Models\SupportTicket;
use App\Models\SupportTopic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OpenSupportTicketCountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('personal_access_tokens')->truncate();
        DB::table('support_tickets')->truncate();
        DB::table('support_topics')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('personal_access_tokens')->truncate();
        DB::table('support_tickets')->truncate();
        DB::table('support_topics')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_admin_can_get_open_support_ticket_count(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $user = User::factory()->create();

        $topic = SupportTopic::factory()->create([
            'support_topic_name' => 'Account Access',
        ]);

        SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'Open ticket one.',
        ]);

        SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::OPEN,
            'ticket_text' => 'Open ticket two.',
        ]);

        SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::CLOSED,
            'ticket_text' => 'Closed ticket.',
        ]);

        $response = $this->getJson('/api/open-support-ticket-count');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('open_support_ticket_count', 2);
    }

    public function test_admin_gets_zero_when_there_are_no_open_support_tickets(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $user = User::factory()->create();

        $topic = SupportTopic::factory()->create([
            'support_topic_name' => 'Account Access',
        ]);

        SupportTicket::factory()->create([
            'user_id' => $user->id,
            'support_topic_id' => $topic->id,
            'status_id' => Status::CLOSED,
            'ticket_text' => 'Closed ticket.',
        ]);

        $response = $this->getJson('/api/open-support-ticket-count');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('open_support_ticket_count', 0);
    }

    public function test_guest_cannot_get_open_support_ticket_count(): void
    {
        $response = $this->getJson('/api/open-support-ticket-count');

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_get_open_support_ticket_count(): void
    {
        $user = User::factory()->create([
            'is_admin' => 0,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/open-support-ticket-count');

        $response->assertStatus(401);
    }
}
