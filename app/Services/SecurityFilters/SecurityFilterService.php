<?php

namespace App\Services\SecurityFilters;

use InvalidArgumentException;

class SecurityFilterService
{
    public function getConfig(): array
    {
        return config('security_filters');
    }

    public function getCategories(): array
    {
        return config('security_filters.categories', []);
    }

    public function getCategory(string $category): array
    {
        $categories = $this->getCategories();

        if (! array_key_exists($category, $categories)) {
            throw new InvalidArgumentException("Invalid security filter category [{$category}].");
        }

        return $categories[$category];
    }

    public function getFiltersByCategory(string $category): array
    {
        return $this->getCategory($category)['filters'];
    }

    public function getFilter(string $category, string $filter): array
    {
        $filters = $this->getFiltersByCategory($category);

        if (! array_key_exists($filter, $filters)) {
            throw new InvalidArgumentException("Invalid security filter [{$filter}] for category [{$category}].");
        }

        return $filters[$filter];
    }

    public function getScopes(): array
    {
        return config('security_filters.scopes', []);
    }

    public function getScope(string $scope): array
    {
        $scopes = $this->getScopes();

        if (! array_key_exists($scope, $scopes)) {
            throw new InvalidArgumentException("Invalid security filter scope [{$scope}].");
        }

        return $scopes[$scope];
    }

    public function getRanges(): array
    {
        return config('security_filters.ranges', []);
    }

    public function getRange(string $range): array
    {
        $ranges = $this->getRanges();

        if (! array_key_exists($range, $ranges)) {
            throw new InvalidArgumentException("Invalid security filter range [{$range}].");
        }

        return $ranges[$range];
    }

    public function getDefaults(): array
    {
        return config('security_filters.defaults', []);
    }

    public function getDefaultCategory(): string
    {
        return $this->getDefaults()['category'];
    }

    public function getDefaultFilter(): string
    {
        return $this->getDefaults()['filter'];
    }

    public function getDefaultScope(): string
    {
        return $this->getDefaults()['scope'];
    }

    public function getDefaultRange(): string
    {
        return $this->getDefaults()['range'];
    }

    public function getDefaultLimit(): int
    {
        return $this->getDefaults()['limit'];
    }

    public function resolveCategory(?string $category): string
    {
        $category = $category ?: $this->getDefaultCategory();

        $this->getCategory($category);

        return $category;
    }

    public function resolveFilter(string $category, ?string $filter): string
    {
        $filter = $filter ?: $this->getDefaultFilter();

        $this->getFilter($category, $filter);

        return $filter;
    }

    public function resolveScope(?string $scope): string
    {
        $scope = $scope ?: $this->getDefaultScope();

        $this->getScope($scope);

        return $scope;
    }

    public function resolveRange(?string $range): string
    {
        $range = $range ?: $this->getDefaultRange();

        $this->getRange($range);

        return $range;
    }

    public function resolveLimit(?int $limit): int
    {
        $limit = $limit ?: $this->getDefaultLimit();

        return max(1, min($limit, 100));
    }

    public function resolve(array $input = []): array
    {
        $category = $this->resolveCategory($input['category'] ?? null);
        $filter = $this->resolveFilter($category, $input['filter'] ?? null);
        $scope = $this->resolveScope($input['scope'] ?? null);
        $range = $this->resolveRange($input['range'] ?? null);
        $limit = $this->resolveLimit($input['limit'] ?? null);

        $filterConfig = $this->getFilter($category, $filter);
        $rangeConfig = $this->getRange($range);
        $scopeConfig = $this->getScope($scope);

        return [
            'category' => $category,
            'filter' => $filter,
            'scope' => $scope,
            'range' => $range,
            'limit' => $limit,

            'filter_config' => $filterConfig,
            'scope_config' => $scopeConfig,
            'range_config' => $rangeConfig,

            'column' => $filterConfig['column'],
            'sort_direction' => $filterConfig['sort_direction'],
            'days' => $rangeConfig['days'],
        ];
    }

    public function getOptions(): array
    {
        return [
            'categories' => $this->getCategories(),
            'scopes' => $this->getScopes(),
            'ranges' => $this->getRanges(),
            'defaults' => $this->getDefaults(),
        ];
    }
}
