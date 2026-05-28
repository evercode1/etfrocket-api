<?php

namespace App\Services\Portfolios;

use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Collection;

class ListPortfoliosService
{
    public function getData(int $userId): Collection
    {
        return Portfolio::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderBy('portfolio_name')
            ->get();
    }
}
