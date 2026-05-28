<?php

namespace App\Utilities;

use Illuminate\Http\Request;

class SortBy
{
    public static function setSortBy(Request $request, array $columns)
    {

        if ($request->has('sortBy')) {

            $sortBy = $request->input('sortBy');

            $sortOrder = $request->input('sortOrder');

            $sortBy = $columns[$sortBy - 1];

            return [$sortBy, $sortOrder];

        }

        $sortBy = $columns[0];

        $sortOrder = 'asc';

        return [$sortBy, $sortOrder];

    }
}
