<?php

namespace App\Queries\MissionControl;

use App\Models\Portfolio;

class PortfolioListQuery
{
    public function getData(int $user_id)
    {
        return Portfolio::where('user_id', $user_id)

            ->orderByDesc('is_default')

            ->orderBy('portfolio_name')

            ->get();
    }
}
