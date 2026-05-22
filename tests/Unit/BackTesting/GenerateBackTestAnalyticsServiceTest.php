<?php

namespace Tests\Unit\BackTesting;

use App\Services\BackTesting\GenerateBackTestAnalyticsService;
use Tests\TestCase;

class GenerateBackTestAnalyticsServiceTest extends TestCase
{
    private GenerateBackTestAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service =
            new GenerateBackTestAnalyticsService();
    }

    public function test_it_returns_empty_analytics_for_empty_chart_rows()
    {
        $data = $this->service->getData(

            chartRows: [],

            initialInvestment: 10000,

        );

        $this->assertEquals(
            0,
            $data['cagr']
        );

        $this->assertEquals(
            0,
            $data['max_drawdown']
        );

        $this->assertEquals(
            0,
            $data['total_return_percentage']
        );
    }

    public function test_it_calculates_total_return_percentage()
    {
        $chartRows = [

            [

                'date' => '2024-01-01',

                'portfolio_value' => 10000,

            ],

            [

                'date' => '2025-01-01',

                'portfolio_value' => 15000,

            ],

        ];

        $data = $this->service->getData(

            chartRows: $chartRows,

            initialInvestment: 10000,

        );

        $this->assertEquals(
            50,
            $data['total_return_percentage']
        );
    }

    public function test_it_calculates_cagr()
    {
        $chartRows = [

            [

                'date' => '2024-01-01',

                'portfolio_value' => 10000,

            ],

            [

                'date' => '2025-01-01',

                'portfolio_value' => 12100,

            ],

        ];

        $data = $this->service->getData(

            chartRows: $chartRows,

            initialInvestment: 10000,

        );

        $this->assertEquals(
            21,
            round($data['cagr'])
        );
    }

    public function test_it_calculates_max_drawdown()
    {
        $chartRows = [

            [

                'date' => '2024-01-01',

                'portfolio_value' => 10000,

            ],

            [

                'date' => '2024-02-01',

                'portfolio_value' => 15000,

            ],

            [

                'date' => '2024-03-01',

                'portfolio_value' => 9000,

            ],

            [

                'date' => '2024-04-01',

                'portfolio_value' => 16000,

            ],

        ];

        $data = $this->service->getData(

            chartRows: $chartRows,

            initialInvestment: 10000,

        );

        $this->assertEquals(
            -40,
            round($data['max_drawdown'])
        );
    }

    public function test_it_handles_flat_growth()
    {
        $chartRows = [

            [

                'date' => '2024-01-01',

                'portfolio_value' => 10000,

            ],

            [

                'date' => '2025-01-01',

                'portfolio_value' => 10000,

            ],

        ];

        $data = $this->service->getData(

            chartRows: $chartRows,

            initialInvestment: 10000,

        );

        $this->assertEquals(
            0,
            round($data['cagr'])
        );

        $this->assertEquals(
            0,
            $data['total_return_percentage']
        );

        $this->assertEquals(
            0,
            $data['max_drawdown']
        );
    }

    public function test_it_handles_negative_returns()
    {
        $chartRows = [

            [

                'date' => '2024-01-01',

                'portfolio_value' => 10000,

            ],

            [

                'date' => '2025-01-01',

                'portfolio_value' => 7500,

            ],

        ];

        $data = $this->service->getData(

            chartRows: $chartRows,

            initialInvestment: 10000,

        );

        $this->assertEquals(
            -25,
            $data['total_return_percentage']
        );

        $this->assertLessThan(
            0,
            $data['cagr']
        );
    }

    public function test_it_uses_initial_investment_as_starting_value()
    {
        $chartRows = [

            [

                'date' => '2024-01-01',

                'portfolio_value' => 5000,

            ],

            [

                'date' => '2025-01-01',

                'portfolio_value' => 15000,

            ],

        ];

        $data = $this->service->getData(

            chartRows: $chartRows,

            initialInvestment: 10000,

        );

        $this->assertEquals(
            50,
            $data['total_return_percentage']
        );
    }

    public function test_it_tracks_drawdown_from_highest_peak()
    {
        $chartRows = [

            [

                'date' => '2024-01-01',

                'portfolio_value' => 10000,

            ],

            [

                'date' => '2024-02-01',

                'portfolio_value' => 20000,

            ],

            [

                'date' => '2024-03-01',

                'portfolio_value' => 10000,

            ],

            [

                'date' => '2024-04-01',

                'portfolio_value' => 18000,

            ],

        ];

        $data = $this->service->getData(

            chartRows: $chartRows,

            initialInvestment: 10000,

        );

        $this->assertEquals(
            -50,
            round($data['max_drawdown'])
        );
    }
}
