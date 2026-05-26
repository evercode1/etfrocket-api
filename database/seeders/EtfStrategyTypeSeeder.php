<?php

namespace Database\Seeders;

use App\Models\EtfStrategyType;
use App\Models\Status;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EtfStrategyTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('etf_strategy_types')->truncate();

        $strategyTypes = [

            'Covered Call',
            'Synthetic Covered Call',
            'Leveraged Covered Call',
            'Single Stock Covered Call',
            '0DTE Covered Call',
            'Option Income',
            'Leveraged ETF',
            'Inverse ETF',
            'Treasury Income',
            'Dividend Growth',
            'High Yield Income',
            'Buy Write',
            'Collar Strategy',
            'Buffer ETF',
            'Target Income',
            'Alternative Income',
            'Collar',
            'Leveraged',
            'Hedged Equity',
            'High Yield Bond',
            'Cash Alternative',
            'Energy Infrastructure',
            'Bond',
            'Crypto Income',
            'Precious Metals',
            'Defense',
            'Risk Managed'

        ];

        foreach ($strategyTypes as $strategyTypeName) {

            EtfStrategyType::create([

                'etf_strategy_type_name' => $strategyTypeName,

            ]);
        }
    }
}
