<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PermissionDeniedTest extends TestCase
{
    public function test_it_returns_permission_denied_response()
    {
        $response = $this->getJson('/api/permission-denied');

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Permission denied',
            ]);
    }
}
