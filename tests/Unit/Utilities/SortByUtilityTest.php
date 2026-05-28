<?php

namespace Tests\Unit\Utilities;

use App\Utilities\SortBy;
use Illuminate\Http\Request;
use Tests\TestCase;

class SortByUtilityTest extends TestCase
{
    public function test_it_returns_first_column_and_ascending_order_by_default()
    {
        $request = new Request;

        $columns = [
            'symbol',
            'fund_name',
            'created_at',
        ];

        [$sortBy, $sortOrder] = SortBy::setSortBy($request, $columns);

        $this->assertEquals('symbol', $sortBy);
        $this->assertEquals('asc', $sortOrder);
    }

    public function test_it_returns_selected_column_and_sort_order()
    {
        $request = new Request([
            'sortBy' => 2,
            'sortOrder' => 'desc',
        ]);

        $columns = [
            'symbol',
            'fund_name',
            'created_at',
        ];

        [$sortBy, $sortOrder] = SortBy::setSortBy($request, $columns);

        $this->assertEquals('fund_name', $sortBy);
        $this->assertEquals('desc', $sortOrder);
    }

    public function test_it_uses_one_based_column_index()
    {
        $request = new Request([
            'sortBy' => 3,
            'sortOrder' => 'asc',
        ]);

        $columns = [
            'symbol',
            'fund_name',
            'created_at',
        ];

        [$sortBy, $sortOrder] = SortBy::setSortBy($request, $columns);

        $this->assertEquals('created_at', $sortBy);
        $this->assertEquals('asc', $sortOrder);
    }
}
