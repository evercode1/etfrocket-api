<?php

namespace Tests\Unit\Config;

use Tests\TestCase;

class SecurityFiltersConfigTest extends TestCase
{
    public function test_security_filters_config_has_expected_top_level_structure(): void
    {
        $config = config('security_filters');

        $this->assertIsArray($config);

        $this->assertArrayHasKey('categories', $config);
        $this->assertArrayHasKey('scopes', $config);
        $this->assertArrayHasKey('ranges', $config);
        $this->assertArrayHasKey('defaults', $config);
    }

    public function test_security_filters_config_has_expected_categories(): void
    {
        $categories = config('security_filters.categories');

        $this->assertIsArray($categories);

        $this->assertArrayHasKey('momentum', $categories);
        $this->assertArrayHasKey('stability', $categories);
        $this->assertArrayHasKey('income', $categories);
        $this->assertArrayHasKey('risk', $categories);
    }

    public function test_each_category_has_required_structure(): void
    {
        $categories = config('security_filters.categories');

        foreach ($categories as $categoryKey => $category) {
            $this->assertArrayHasKey('label', $category, "Missing label for {$categoryKey}");
            $this->assertArrayHasKey('description', $category, "Missing description for {$categoryKey}");
            $this->assertArrayHasKey('filters', $category, "Missing filters for {$categoryKey}");

            $this->assertIsString($category['label']);
            $this->assertIsString($category['description']);
            $this->assertIsArray($category['filters']);
            $this->assertNotEmpty($category['filters']);
        }
    }

    public function test_each_filter_has_required_structure(): void
    {
        $categories = config('security_filters.categories');

        foreach ($categories as $categoryKey => $category) {
            foreach ($category['filters'] as $filterKey => $filter) {
                $message = "Invalid filter {$categoryKey}.{$filterKey}";

                $this->assertArrayHasKey('label', $filter, $message);
                $this->assertArrayHasKey('description', $filter, $message);
                $this->assertArrayHasKey('column', $filter, $message);
                $this->assertArrayHasKey('sort_direction', $filter, $message);
                $this->assertArrayHasKey('default_range', $filter, $message);

                $this->assertIsString($filter['label'], $message);
                $this->assertIsString($filter['description'], $message);
                $this->assertIsString($filter['column'], $message);
                $this->assertContains($filter['sort_direction'], ['asc', 'desc'], $message);
                $this->assertIsString($filter['default_range'], $message);
            }
        }
    }

    public function test_filter_default_ranges_exist_in_ranges_config(): void
    {
        $categories = config('security_filters.categories');

        $ranges = array_keys(config('security_filters.ranges'));

        foreach ($categories as $categoryKey => $category) {
            foreach ($category['filters'] as $filterKey => $filter) {
                $this->assertContains(
                    $filter['default_range'],
                    $ranges,
                    "Default range [{$filter['default_range']}] for {$categoryKey}.{$filterKey} is not defined in ranges config."
                );
            }
        }
    }

    public function test_default_category_filter_scope_and_range_are_valid(): void
    {
        $defaults = config('security_filters.defaults');

        $this->assertArrayHasKey('category', $defaults);
        $this->assertArrayHasKey('filter', $defaults);
        $this->assertArrayHasKey('scope', $defaults);
        $this->assertArrayHasKey('range', $defaults);
        $this->assertArrayHasKey('limit', $defaults);

        $categories = config('security_filters.categories');
        $scopes = config('security_filters.scopes');
        $ranges = config('security_filters.ranges');

        $this->assertArrayHasKey($defaults['category'], $categories);
        $this->assertArrayHasKey($defaults['scope'], $scopes);
        $this->assertArrayHasKey($defaults['range'], $ranges);

        $defaultCategory = $categories[$defaults['category']];

        $this->assertArrayHasKey($defaults['filter'], $defaultCategory['filters']);

        $this->assertIsInt($defaults['limit']);
        $this->assertGreaterThan(0, $defaults['limit']);
    }

    public function test_scopes_have_required_structure(): void
    {
        $scopes = config('security_filters.scopes');

        $this->assertArrayHasKey('all', $scopes);
        $this->assertArrayHasKey('owned', $scopes);

        foreach ($scopes as $scopeKey => $scope) {
            $this->assertArrayHasKey('label', $scope, "Missing label for scope {$scopeKey}");
            $this->assertArrayHasKey('description', $scope, "Missing description for scope {$scopeKey}");

            $this->assertIsString($scope['label']);
            $this->assertIsString($scope['description']);
        }
    }

    public function test_ranges_have_required_structure(): void
    {
        $ranges = config('security_filters.ranges');

        $this->assertArrayHasKey('latest', $ranges);
        $this->assertArrayHasKey('30d', $ranges);
        $this->assertArrayHasKey('90d', $ranges);
        $this->assertArrayHasKey('1y', $ranges);

        foreach ($ranges as $rangeKey => $range) {
            $this->assertArrayHasKey('label', $range, "Missing label for range {$rangeKey}");
            $this->assertArrayHasKey('days', $range, "Missing days for range {$rangeKey}");

            $this->assertIsString($range['label']);

            if ($rangeKey === 'latest') {
                $this->assertNull($range['days']);
            } else {
                $this->assertIsInt($range['days']);
                $this->assertGreaterThan(0, $range['days']);
            }
        }
    }

    public function test_category_display_orders_are_unique_and_sequential(): void
    {
        $categories = config('security_filters.categories');

        $orders = collect($categories)
            ->pluck('display_order')
            ->sort()
            ->values()
            ->toArray();

        $expected = range(1, count($orders));

        $this->assertSame($expected, $orders);
    }
}
