<?php

namespace Database\Seeders;

use App\Models\DistributionFrequency;
use App\Models\Etf;
use App\Models\EtfIssuer;
use App\Models\EtfStrategyType;
use App\Models\Status;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EtfSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('etfs')->truncate();


        $etfs = [

            [
                'symbol' => 'CHPY',
                'fund_name' => 'YieldMax Semiconductor Portfolio Option Income ETF',
                'etf_issuer_id' => EtfIssuer::YIELDMAX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 1.03,
                'inception_date' => '2025-04-02',
                'source' => 'official_issuer_page',
                'website_url' => 'https://yieldmaxetfs.com/our-etfs/chpy/',
                'notes' => null,
            ],

            [
                'symbol' => 'AMDY',
                'fund_name' => 'YieldMax AMD Option Income Strategy ETF',
                'etf_issuer_id' => EtfIssuer::YIELDMAX,
                'etf_strategy_type_id' => EtfStrategyType::SYNTHETIC_COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 1.23,
                'inception_date' => '2023-09-18',
                'source' => 'official_issuer_page',
                'website_url' => 'https://yieldmaxetfs.com/our-etfs/amdy/',
                'notes' => null,
            ],

            [
                'symbol' => 'NVII',
                'fund_name' => 'REX NVDA Growth & Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::LEVERAGED_COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2025-05-28',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/nvii/',
                'notes' => null,
            ],

            [
                'symbol' => 'GOOY',
                'fund_name' => 'YieldMax GOOGL Option Income Strategy ETF',
                'etf_issuer_id' => EtfIssuer::YIELDMAX,
                'etf_strategy_type_id' => EtfStrategyType::SYNTHETIC_COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2023-07-27',
                'source' => 'official_issuer_page',
                'website_url' => 'https://yieldmaxetfs.com/our-etfs/gooy/',
                'notes' => null,
            ],

            [
                'symbol' => 'BLOX',
                'fund_name' => 'Nicholas Crypto Income ETF',
                'etf_issuer_id' => EtfIssuer::NICHOLASX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2025-06-16',
                'source' => 'official_issuer_page',
                'website_url' => 'https://nicholasx.com/blox/',
                'notes' => null,
            ],

            [
                'symbol' => 'LFGY',
                'fund_name' => 'YieldMax Crypto Industry & Tech Portfolio Option Income ETF',
                'etf_issuer_id' => EtfIssuer::YIELDMAX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 1.02,
                'inception_date' => '2025-01-13',
                'source' => 'official_issuer_page',
                'website_url' => 'https://yieldmaxetfs.com/our-etfs/lfgy/',
                'notes' => null,
            ],

            [
                'symbol' => 'QQQI',
                'fund_name' => 'NEOS Nasdaq-100 High Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.68,
                'inception_date' => '2024-01-29',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/qqqi/',
                'notes' => null,
            ],

        ];

        foreach ($etfs as $etf) {

            Etf::create($etf);
        }
    }
}
