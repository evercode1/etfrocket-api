<?php

namespace Tests\Feature\Support\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetSupportReplyFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_admin_can_get_support_reply_form_config()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/get-support-reply-form');

        $response->assertOk()
            ->assertJson([
                ' status' => 'success',
                'section_heading' => 'Reply To Ticket',
                'action_type' => 'post',
                'post_endpoint' => 'support-reply-to-ticket',
                'form_config' => [
                    [
                        'name' => 'response_text',
                        'type' => 'textarea',
                        'label' => 'Response Text',
                        'required' => 1,
                        'max_length' => 50,
                        'instructions' => '',
                    ],
                ],
            ]);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/get-support-reply-form');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
