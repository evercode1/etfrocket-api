<?php

namespace Tests\Unit\Securities;

use App\Services\Comparisons\SecurityComparisonService;
use InvalidArgumentException;
use Tests\TestCase;

class SecurityComparisonServiceTest extends TestCase
{
    public function test_it_can_return_comparison_options(): void
    {
        $options = (new SecurityComparisonService)->getOptions();

        $this->assertArrayHasKey('metrics', $options);
        $this->assertArrayHasKey('ranges', $options);
        $this->assertArrayHasKey('defaults', $options);

        $this->assertIsArray($options['metrics']);
        $this->assertIsArray($options['ranges']);
        $this->assertIsArray($options['defaults']);
    }

    public function test_it_can_get_a_valid_metric(): void
    {
        $metric = (new SecurityComparisonService)->getMetric('price');

        $this->assertSame('Price', $metric['label']);
        $this->assertSame('security_price_histories', $metric['table']);
        $this->assertSame('price_date', $metric['date_column']);
        $this->assertSame('close_price', $metric['value_column']);
    }

    public function test_it_throws_exception_for_invalid_metric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid security comparison metric [bad_metric].');

        (new SecurityComparisonService)->getMetric('bad_metric');
    }

    public function test_it_can_get_a_valid_range(): void
    {
        $days = (new SecurityComparisonService)->getRange('1y');

        $this->assertSame(365, $days);
    }

    public function test_it_throws_exception_for_invalid_range(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid security comparison range [bad_range].');

        (new SecurityComparisonService)->getRange('bad_range');
    }

    public function test_it_resolves_default_metric_and_range(): void
    {
        $service = new SecurityComparisonService;

        $this->assertSame('price', $service->resolveMetric(null));
        $this->assertSame('1y', $service->resolveRange(null));
    }

    public function test_it_resolves_security_ids_from_comma_separated_string(): void
    {
        $securityIds = (new SecurityComparisonService)->resolveSecurityIds('1,2,3');

        $this->assertSame([1, 2, 3], $securityIds);
    }

    public function test_it_resolves_security_ids_from_array(): void
    {
        $securityIds = (new SecurityComparisonService)->resolveSecurityIds([1, '2', 3]);

        $this->assertSame([1, 2, 3], $securityIds);
    }

    public function test_it_removes_duplicate_and_invalid_security_ids(): void
    {
        $securityIds = (new SecurityComparisonService)->resolveSecurityIds(['1', '2', '2', 'abc', 0, -4, 3]);

        $this->assertSame([1, 2, 3], $securityIds);
    }

    public function test_it_requires_at_least_one_security_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one security is required for comparison.');

        (new SecurityComparisonService)->resolveSecurityIds(null);
    }

    public function test_it_requires_at_least_one_valid_security_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one valid security is required for comparison.');

        (new SecurityComparisonService)->resolveSecurityIds(['abc', 0, -1]);
    }

    public function test_it_prevents_comparing_too_many_securities(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You may compare up to 5 securities at one time.');

        (new SecurityComparisonService)->resolveSecurityIds([1, 2, 3, 4, 5, 6]);
    }

    public function test_it_resolves_complete_comparison_input(): void
    {
        $resolved = (new SecurityComparisonService)->resolve([
            'metric' => 'price',
            'range' => '90d',
            'security_ids' => '1,2,3',
        ]);

        $this->assertSame('price', $resolved['metric']);
        $this->assertSame('90d', $resolved['range']);
        $this->assertSame(90, $resolved['days']);
        $this->assertSame([1, 2, 3], $resolved['security_ids']);

        $this->assertSame('security_price_histories', $resolved['table']);
        $this->assertSame('price_date', $resolved['date_column']);
        $this->assertSame('close_price', $resolved['value_column']);
    }

    public function test_it_resolves_defaults_when_metric_and_range_are_missing(): void
    {
        $resolved = (new SecurityComparisonService)->resolve([
            'security_ids' => [1, 2],
        ]);

        $this->assertSame('price', $resolved['metric']);
        $this->assertSame('1y', $resolved['range']);
        $this->assertSame(365, $resolved['days']);
        $this->assertSame([1, 2], $resolved['security_ids']);
    }
}
