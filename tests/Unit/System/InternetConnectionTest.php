<?php

namespace Tests\Unit\System;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InternetConnectionTest extends TestCase
{
    public function test_dev_environment_can_reach_the_internet()
    {
        $response = Http::timeout(10)
            ->get('https://api.github.com');

        $this->assertTrue($response->successful());

        $this->assertEquals(200, $response->status());

        $this->assertArrayHasKey(
            'current_user_url',
            $response->json()
        );
    }
}
