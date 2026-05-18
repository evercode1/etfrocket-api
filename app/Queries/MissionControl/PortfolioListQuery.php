<?php

namespace App\Queries\MissionControl;

use App\Models\Portfolio;

class PortfolioListQuery
{
    public function getData(int $user_id)
    {
        $portfolios = Portfolio::where('user_id', $user_id)

            ->get();

        return $portfolios;
    }
}
