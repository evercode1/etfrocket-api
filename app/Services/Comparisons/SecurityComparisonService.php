<?php

namespace App\Services\Comparisons;

use InvalidArgumentException;

class SecurityComparisonService
{
    public function getConfig(): array
    {

        $security_config = config('security_comparison');

        return $security_config ?: [];
    }

    public function getMetrics(): array
    {
        $metrics = config('security_comparison.metrics');

        return $metrics ?: [];
    }

    public function getMetric(string $metric): array
    {
        $metrics = $this->getMetrics();

        if (! array_key_exists($metric, $metrics)) {
            throw new InvalidArgumentException("Invalid security comparison metric [{$metric}].");
        }

        return $metrics[$metric];
    }

    public function getRanges(): array
    {
        $ranges = config('security_comparison.ranges');

        return $ranges ?: [];
    }

    public function getRange(string $range): int|string
    {
        $ranges = $this->getRanges();

        if (! array_key_exists($range, $ranges)) {
            throw new InvalidArgumentException("Invalid security comparison range [{$range}].");
        }

        return $ranges[$range];
    }

    public function getDefaults(): array
    {
        $defaults = config('security_comparison.defaults');

        return $defaults ?: [];
    }

    public function getDefaultMetric(): string
    {
        return $this->getDefaults()['metric'];
    }

    public function getDefaultRange(): string
    {
        return $this->getDefaults()['range'];
    }

    public function getMaxSecurities(): int
    {
        return $this->getDefaults()['max_securities'];
    }

    public function resolveMetric(?string $metric): string
    {
        $metric = $metric ?: $this->getDefaultMetric();

        $this->getMetric($metric);

        return $metric;
    }

    public function resolveRange(?string $range): string
    {
        $range = $range ?: $this->getDefaultRange();

        $this->getRange($range);

        return $range;
    }

    public function resolveSecurityIds(array|string|null $securityIds): array
    {
        if (is_null($securityIds)) {
            throw new InvalidArgumentException('At least one security is required for comparison.');
        }

        if (is_string($securityIds)) {
            $securityIds = explode(',', $securityIds);
        }

        $securityIds = collect($securityIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->toArray();

        if (empty($securityIds)) {
            throw new InvalidArgumentException('At least one valid security is required for comparison.');
        }

        if (count($securityIds) > $this->getMaxSecurities()) {
            throw new InvalidArgumentException("You may compare up to {$this->getMaxSecurities()} securities at one time.");
        }

        return $securityIds;
    }

    public function resolve(array $input = []): array
    {
        $metric = $this->resolveMetric($input['metric'] ?? null);
        $range = $this->resolveRange($input['range'] ?? null);
        $securityIds = $this->resolveSecurityIds($input['security_ids'] ?? null);

        $metricConfig = $this->getMetric($metric);
        $days = $this->getRange($range);

        return [

            'metric' => $metric,

            'range' => $range,

            'days' => $days,

            'security_ids' => $securityIds,

            'metric_config' => $metricConfig,

            'table' => $metricConfig['table'] ?? null,

            'date_column' => $metricConfig['date_column'] ?? null,

            'value_column' => $metricConfig['value_column'] ?? null,

        ];
    }

    public function getOptions(): array
    {
        return [
            'metrics' => $this->getMetrics(),
            'ranges' => $this->getRanges(),
            'defaults' => $this->getDefaults(),
        ];
    }
}
