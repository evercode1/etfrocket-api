<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;

class PortfoliosSeederController extends Controller
{
    public function run(): void
    {

        Portfolio::truncate();
        PortfolioTransaction::truncate();

        // 1

        Portfolio::create([
            'portfolio_name' => 'Main Portfolio',
            'status_id' => 4,
            'is_default' => 1,
            'user_id' => 2,
        ]);

        // 2

        Portfolio::create([
            'portfolio_name' => 'Retirement Portfolio',
            'status_id' => 4,
            'is_default' => 1,
            'user_id' => 2,
        ]);

        // Protfolio Transaction

        PortfolioTransaction::factory()
            ->count(100)
            ->create();
    }
}
