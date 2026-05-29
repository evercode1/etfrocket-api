<?php

namespace Tests\Feature\Filters;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetEtfFiltersTest extends TestCase
{
    public function test_authenticated_user_can_get_etf_filters(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/get-security-filters');

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'categories' => [
                    'momentum' => [
                        'display_order',
                        'label',
                        'description',
                        'filters',
                    ],
                    'stability' => [
                        'display_order',
                        'label',
                        'description',
                        'filters',
                    ],
                    'income' => [
                        'display_order',
                        'label',
                        'description',
                        'filters',
                    ],
                    'risk' => [
                        'display_order',
                        'label',
                        'description',
                        'filters',
                    ],
                ],
                'scopes' => [
                    'all' => [
                        'display_order',
                        'label',
                        'description',
                    ],
                    'owned' => [
                        'display_order',
                        'label',
                        'description',
                    ],
                ],
                'ranges' => [
                    'latest' => [
                        'display_order',
                        'label',
                        'days',
                    ],
                    '30d' => [
                        'display_order',
                        'label',
                        'days',
                    ],
                    '90d' => [
                        'display_order',
                        'label',
                        'days',
                    ],
                    '1y' => [
                        'display_order',
                        'label',
                        'days',
                    ],
                ],
                'defaults' => [
                    'category',
                    'filter',
                    'scope',
                    'range',
                    'limit',
                ],
            ],
        ]);
    }

    public function test_guest_cannot_get_security_filters(): void
    {
        $response = $this->getJson('/api/get-security-filters');

        $response->assertStatus(401);
    }

    public function test_get_security_filters_returns_expected_default_values(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/get-security-filters');

        $response->assertStatus(200);

        $response->assertJsonPath('data.defaults.category', 'momentum');
        $response->assertJsonPath('data.defaults.filter', 'highest_total_return_percentage');
        $response->assertJsonPath('data.defaults.scope', 'all');
        $response->assertJsonPath('data.defaults.range', '1y');
        $response->assertJsonPath('data.defaults.limit', 25);
    }

    public function test_get_security_filters_returns_display_order_values(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/get-security-filters');

        $response->assertStatus(200);

        $response->assertJsonPath('data.categories.momentum.display_order', 1);
        $response->assertJsonPath('data.categories.stability.display_order', 2);
        $response->assertJsonPath('data.categories.income.display_order', 3);
        $response->assertJsonPath('data.categories.risk.display_order', 4);

        $response->assertJsonPath('data.scopes.all.display_order', 1);
        $response->assertJsonPath('data.scopes.owned.display_order', 2);

        $response->assertJsonPath('data.ranges.latest.display_order', 1);
        $response->assertJsonPath('data.ranges.30d.display_order', 2);
        $response->assertJsonPath('data.ranges.90d.display_order', 3);
        $response->assertJsonPath('data.ranges.1y.display_order', 4);
    }

    public function test_get_security_filters_returns_expected_momentum_filter_values(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/get-security-filters');

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.categories.momentum.filters.highest_total_return_percentage.display_order',
            1
        );

        $response->assertJsonPath(
            'data.categories.momentum.filters.highest_total_return_percentage.column',
            'total_return_percentage'
        );

        $response->assertJsonPath(
            'data.categories.momentum.filters.highest_total_return_percentage.sort_direction',
            'desc'
        );

        $response->assertJsonPath(
            'data.categories.momentum.filters.highest_total_return_percentage.default_range',
            '1y'
        );
    }
}
