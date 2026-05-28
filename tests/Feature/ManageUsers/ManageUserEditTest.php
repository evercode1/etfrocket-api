<?php

namespace Tests\Feature\ManageUsers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManageUserEditTest extends TestCase
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

    public function test_admin_can_get_user_edit_form_config()
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'Managed User',
            'email' => 'managed@example.com',
            'is_active' => 1,
            'is_influencer' => 0,
            'is_subscriber' => 1,
            'is_admin' => 0,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/manage-user/edit/'.$user->id);

        $response->assertOk()
            ->assertJson([
                'section_heading' => 'Edit User',
                'request_type' => 'post',
                'post_endpoint' => 'manage-user/'.$user->id,
                'form_configs' => [
                    [
                        'name' => 'name',
                        'type' => 'text',
                        'label' => 'Name',
                        'required' => 1,
                        'max_length' => 50,
                        'default_value' => '',
                        'instructions' => '',
                        'old_value' => 'Managed User',
                    ],
                    [
                        'name' => 'email',
                        'type' => 'text',
                        'label' => 'Email',
                        'required' => 1,
                        'max_length' => 50,
                        'default_value' => '',
                        'instructions' => '',
                        'old_value' => 'managed@example.com',
                    ],
                    [
                        'name' => 'is_active',
                        'type' => 'boolean',
                        'label' => 'Is Active',
                        'required' => 1,
                        'max_length' => 50,
                        'default_value' => '',
                        'instructions' => '',
                    ],
                    [
                        'name' => 'is_influencer',
                        'type' => 'boolean',
                        'label' => 'Is Influencer',
                        'required' => 1,
                        'max_length' => 50,
                        'default_value' => '',
                        'instructions' => '',
                    ],
                    [
                        'name' => 'is_subscriber',
                        'type' => 'boolean',
                        'label' => 'Is Subscriber',
                        'required' => 1,
                        'max_length' => 50,
                        'default_value' => '',
                        'instructions' => '',
                    ],
                    [
                        'name' => 'is_admin',
                        'type' => 'boolean',
                        'label' => 'Is Admin',
                        'required' => 1,
                        'max_length' => 50,
                        'default_value' => '',
                        'instructions' => '',
                    ],
                ],
            ])
            ->assertJsonPath('form_configs.2.old_value', 1)
            ->assertJsonPath('form_configs.3.old_value', false)
            ->assertJsonPath('form_configs.4.old_value', 1)
            ->assertJsonPath('form_configs.5.old_value', false);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/manage-user/edit/1');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
