<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\DistributionFrequency;
use App\Models\Etf;
use App\Models\EtfIssuer;
use App\Models\EtfStrategyType;
use App\Models\Status;
use Illuminate\Support\Facades\DB;

class EtfsSeederController extends Controller
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
            [

                'symbol' => 'ABNY',

                'fund_name' => 'YieldMax ABNB Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-01-10',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/abny/',

                'notes' => null,

            ],

            [

                'symbol' => 'AIYY',

                'fund_name' => 'YieldMax AI Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-01-10',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/aiyy/',

                'notes' => null,

            ],

            [

                'symbol' => 'AMZY',

                'fund_name' => 'YieldMax AMZN Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.45,

                'inception_date' => '2023-09-19',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/amzy/',

                'notes' => null,

            ],

            [

                'symbol' => 'APLY',

                'fund_name' => 'YieldMax AAPL Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2023-08-21',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/aply/',

                'notes' => null,

            ],

            [

                'symbol' => 'BABO',

                'fund_name' => 'YieldMax BABA Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2025-01-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/babo/',

                'notes' => null,

            ],

            [

                'symbol' => 'BIGY',

                'fund_name' => 'YieldMax Big Tech Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-11-18',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/bigy/',

                'notes' => 'YieldMax Lite ETF',

            ],

            [

                'symbol' => 'BRKC',

                'fund_name' => 'YieldMax BRK.B Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2025-01-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/brkc/',

                'notes' => null,

            ],

            [

                'symbol' => 'CONY',

                'fund_name' => 'YieldMax COIN Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2023-08-15',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/cony/',

                'notes' => null,

            ],

            [

                'symbol' => 'CRCO',

                'fund_name' => 'YieldMax CRCL Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2026-04-29',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/crco/',

                'notes' => null,

            ],

            [

                'symbol' => 'CRSH',

                'fund_name' => 'YieldMax Short TSLA Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2024-01-24',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/crsh/',

                'notes' => 'Short strategy ETF',

            ],
            [

                'symbol' => 'CVNY',

                'fund_name' => 'YieldMax CVNA Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2026-02-18',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/cvny/',

                'notes' => null,

            ],

            [

                'symbol' => 'DDDD',

                'fund_name' => 'YieldMax TSLA TSLL Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-08-20',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/dddd/',

                'notes' => null,

            ],

            [

                'symbol' => 'DIPS',

                'fund_name' => 'YieldMax Short NVDA Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2024-01-24',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/dips/',

                'notes' => 'Short strategy ETF',

            ],

            [

                'symbol' => 'DISO',

                'fund_name' => 'YieldMax DIS Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2025-02-12',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/diso/',

                'notes' => null,

            ],

            [

                'symbol' => 'DRAY',

                'fund_name' => 'YieldMax DKNG Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2025-02-12',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/dray/',

                'notes' => null,

            ],

            [

                'symbol' => 'FBY',

                'fund_name' => 'YieldMax META Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2023-08-10',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/fby/',

                'notes' => null,

            ],

            [

                'symbol' => 'FEAT',

                'fund_name' => 'YieldMax Dorsey Wright Featured 5 Income ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-10-22',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/feat/',

                'notes' => 'Portfolio strategy ETF',

            ],

            [

                'symbol' => 'FIAT',

                'fund_name' => 'YieldMax Short COIN Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2024-01-18',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/fiat/',

                'notes' => 'Short strategy ETF',

            ],

            [

                'symbol' => 'FIVY',

                'fund_name' => 'YieldMax Dorsey Wright Hybrid 5 Income ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-10-22',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/fivy/',

                'notes' => 'Portfolio strategy ETF',

            ],

            [

                'symbol' => 'GDXY',

                'fund_name' => 'YieldMax Gold Miners Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-08-21',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/gdxy/',

                'notes' => null,

            ],

            [

                'symbol' => 'GMEY',

                'fund_name' => 'YieldMax GME Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2025-02-12',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/gmey/',

                'notes' => null,

            ],

            [

                'symbol' => 'GPTY',

                'fund_name' => 'YieldMax PLTR Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-12-11',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/gpty/',

                'notes' => null,

            ],
            [

                'symbol' => 'HIYY',

                'fund_name' => 'YieldMax HIMS Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2025-01-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/hiyy/',

                'notes' => null,

            ],

            [

                'symbol' => 'HOOY',

                'fund_name' => 'YieldMax HOOD Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2025-01-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/hooy/',

                'notes' => null,

            ],

            [

                'symbol' => 'JPO',

                'fund_name' => 'YieldMax JPM Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-07-16',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/jpo/',

                'notes' => null,

            ],

            [

                'symbol' => 'JPMO',

                'fund_name' => 'YieldMax JPM Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2025-03-05',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/jpmo/',

                'notes' => null,

            ],

            [

                'symbol' => 'MARO',

                'fund_name' => 'YieldMax MARA Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2023-10-19',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/maro/',

                'notes' => null,

            ],

            [

                'symbol' => 'MINY',

                'fund_name' => 'YieldMax MRNA Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-03-20',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/miny/',

                'notes' => null,

            ],

            [

                'symbol' => 'MRNY',

                'fund_name' => 'YieldMax MRNA Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2023-10-18',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/mrny/',

                'notes' => null,

            ],

            [

                'symbol' => 'MSFO',

                'fund_name' => 'YieldMax MSFT Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2023-08-10',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/msfo/',

                'notes' => null,

            ],

            [

                'symbol' => 'MSST',

                'fund_name' => 'YieldMax MSTR Performance & Distribution Target 25 ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2026-02-19',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/msst/',

                'notes' => 'YieldMax Lite ETF',

            ],

            [

                'symbol' => 'MSTY',

                'fund_name' => 'YieldMax MSTR Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-02-21',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/msty/',

                'notes' => null,

            ],

            [

                'symbol' => 'NFLY',

                'fund_name' => 'YieldMax NFLX Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2023-08-10',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/nfly/',

                'notes' => null,

            ],

            [

                'symbol' => 'NVIT',

                'fund_name' => 'YieldMax NVDA Performance & Distribution Target 25 ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2026-02-19',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/nvit/',

                'notes' => 'YieldMax Lite ETF',

            ],
            [

                'symbol' => 'NVDY',

                'fund_name' => 'YieldMax NVDA Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2023-05-10',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/nvdy/',

                'notes' => null,

            ],

            [

                'symbol' => 'OARK',

                'fund_name' => 'YieldMax Innovation Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.29,

                'inception_date' => '2023-11-30',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/oark/',

                'notes' => 'ARKK-focused strategy ETF',

            ],

            [

                'symbol' => 'PLTY',

                'fund_name' => 'YieldMax PLTR Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-01-17',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/plty/',

                'notes' => null,

            ],

            [

                'symbol' => 'PYPY',

                'fund_name' => 'YieldMax PYPL Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-06-05',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/pypy/',

                'notes' => null,

            ],

            [

                'symbol' => 'QDTY',

                'fund_name' => 'YieldMax Nasdaq 100 0DTE Covered Call Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-09-18',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/qdty/',

                'notes' => '0DTE strategy ETF',

            ],

            [

                'symbol' => 'RBLY',

                'fund_name' => 'YieldMax RBLX Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-09-18',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/rbly/',

                'notes' => null,

            ],

            [

                'symbol' => 'RDYY',

                'fund_name' => 'YieldMax RDDT Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2025-02-12',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/rdyy/',

                'notes' => null,

            ],

            [

                'symbol' => 'RDTY',

                'fund_name' => 'YieldMax Russell 2000 0DTE Covered Call Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-09-18',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/rdty/',

                'notes' => '0DTE strategy ETF',

            ],

            [

                'symbol' => 'RNTY',

                'fund_name' => 'YieldMax Target 12 Real Estate Option Income ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2026-03-12',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/rnty/',

                'notes' => 'Target 12 ETF',

            ],

            [

                'symbol' => 'SDTY',

                'fund_name' => 'YieldMax S&P 500 0DTE Covered Call Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-09-18',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/sdty/',

                'notes' => '0DTE strategy ETF',

            ],

            [

                'symbol' => 'SMCY',

                'fund_name' => 'YieldMax SMCI Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-07-17',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/smcy/',

                'notes' => null,

            ],

            [

                'symbol' => 'SNOY',

                'fund_name' => 'YieldMax SNOW Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-05-15',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/snoy/',

                'notes' => null,

            ],

            [

                'symbol' => 'SOXY',

                'fund_name' => 'YieldMax SOXL Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-04-16',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/soxy/',

                'notes' => null,

            ],
            [

                'symbol' => 'TEST',

                'fund_name' => 'YieldMax Test ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::INACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-01-01',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/test/',

                'notes' => 'Testing or placeholder ETF',

            ],

            [

                'symbol' => 'TSLY',

                'fund_name' => 'YieldMax TSLA Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2022-11-22',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/tsly/',

                'notes' => null,

            ],

            [

                'symbol' => 'TSMY',

                'fund_name' => 'YieldMax TSM Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-06-05',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/tsmy/',

                'notes' => null,

            ],

            [

                'symbol' => 'ULTY',

                'fund_name' => 'YieldMax Ultra Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.24,

                'inception_date' => '2024-02-28',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/ulty/',

                'notes' => 'Multi-strategy portfolio ETF',

            ],

            [

                'symbol' => 'WNTR',

                'fund_name' => 'YieldMax MSTR Short Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.14,

                'inception_date' => '2025-01-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/wntr/',

                'notes' => 'Short strategy ETF',

            ],

            [

                'symbol' => 'XOMO',

                'fund_name' => 'YieldMax XOM Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-05-15',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/xomo/',

                'notes' => null,

            ],

            [

                'symbol' => 'XYZY',

                'fund_name' => 'YieldMax XYZ Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-07-16',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/xyzy/',

                'notes' => null,

            ],

            [

                'symbol' => 'YBIT',

                'fund_name' => 'YieldMax Bitcoin Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2024-01-17',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/ybit/',

                'notes' => 'Bitcoin-related strategy ETF',

            ],

            [

                'symbol' => 'YMAG',

                'fund_name' => 'YieldMax Magnificent 7 Fund of Option Income ETFs',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.28,

                'inception_date' => '2024-01-29',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/ymag/',

                'notes' => 'Fund of funds ETF',

            ],

            [

                'symbol' => 'YMAX',

                'fund_name' => 'YieldMax Universe Fund of Option Income ETFs',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 1.28,

                'inception_date' => '2024-01-29',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/ymax/',

                'notes' => 'Fund of funds ETF',

            ],

            [

                'symbol' => 'YQQQ',

                'fund_name' => 'YieldMax QQQ Option Income Strategy ETF',

                'etf_issuer_id' => EtfIssuer::YIELDMAX,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-06-18',

                'source' => 'official_issuer_page',

                'website_url' => 'https://yieldmaxetfs.com/our-etfs/yqqq/',

                'notes' => null,

            ],

            [

                'symbol' => 'AAPW',

                'fund_name' => 'Roundhill AAPL WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/aapw/',

                'notes' => null,

            ],

            [

                'symbol' => 'AMDW',

                'fund_name' => 'Roundhill AMD WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/amdw/',

                'notes' => null,

            ],

            [

                'symbol' => 'AMZW',

                'fund_name' => 'Roundhill AMZN WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/amzw/',

                'notes' => null,

            ],

            [

                'symbol' => 'ARMW',

                'fund_name' => 'Roundhill ARM WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-05-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/armw/',

                'notes' => null,

            ],

            [

                'symbol' => 'AVGW',

                'fund_name' => 'Roundhill AVGO WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/avgw/',

                'notes' => null,

            ],

            [

                'symbol' => 'BABW',

                'fund_name' => 'Roundhill BABA WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-05-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/babw/',

                'notes' => null,

            ],

            [

                'symbol' => 'BRKW',

                'fund_name' => 'Roundhill BRK.B WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-05-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/brkw/',

                'notes' => null,

            ],

            [

                'symbol' => 'COIW',

                'fund_name' => 'Roundhill COIN WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/coiw/',

                'notes' => null,

            ],

            [

                'symbol' => 'COSW',

                'fund_name' => 'Roundhill COST WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-05-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/cosw/',

                'notes' => null,

            ],

            [

                'symbol' => 'GDXW',

                'fund_name' => 'Roundhill Gold Miners WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-05-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/gdxw/',

                'notes' => null,

            ],

            [

                'symbol' => 'GLDW',

                'fund_name' => 'Roundhill Gold WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-05-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/gldw/',

                'notes' => null,

            ],

            [

                'symbol' => 'GOOW',

                'fund_name' => 'Roundhill GOOGL WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/goow/',

                'notes' => null,

            ],

            [

                'symbol' => 'HOOW',

                'fund_name' => 'Roundhill HOOD WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-05-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/hoow/',

                'notes' => null,

            ],

            [

                'symbol' => 'METW',

                'fund_name' => 'Roundhill META WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/metw/',

                'notes' => null,

            ],

            [

                'symbol' => 'MSFW',

                'fund_name' => 'Roundhill MSFT WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/msfw/',

                'notes' => null,

            ],

            [

                'symbol' => 'MSTW',

                'fund_name' => 'Roundhill MSTR WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/mstw/',

                'notes' => null,

            ],

            [

                'symbol' => 'NFLW',

                'fund_name' => 'Roundhill NFLX WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/nflw/',

                'notes' => null,

            ],

            [

                'symbol' => 'NVDW',

                'fund_name' => 'Roundhill NVDA WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/nvdw/',

                'notes' => null,

            ],

            [

                'symbol' => 'PLTW',

                'fund_name' => 'Roundhill PLTR WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-05-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/pltw/',

                'notes' => null,

            ],

            [

                'symbol' => 'QDTE',

                'fund_name' => 'Roundhill Innovation-100 0DTE Covered Call Strategy ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.95,

                'inception_date' => '2024-03-07',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/qdte/',

                'notes' => '0DTE strategy ETF',

            ],

            [

                'symbol' => 'RDTE',

                'fund_name' => 'Roundhill Russell 2000 0DTE Covered Call Strategy ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.95,

                'inception_date' => '2024-09-19',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/rdte/',

                'notes' => '0DTE strategy ETF',

            ],

            [

                'symbol' => 'TOPW',

                'fund_name' => 'Roundhill Top 10 WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-10-02',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/topw/',

                'notes' => 'Portfolio strategy ETF',

            ],

            [

                'symbol' => 'TPAY',

                'fund_name' => 'Roundhill T-Bill & Option Income ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::MONTHLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.49,

                'inception_date' => '2024-10-10',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/tpay/',

                'notes' => 'Treasury income strategy ETF',

            ],

            [

                'symbol' => 'TSLW',

                'fund_name' => 'Roundhill TSLA WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-03-06',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/tslw/',

                'notes' => null,

            ],

            [

                'symbol' => 'TSYW',

                'fund_name' => 'Roundhill TSM WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-05-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/tsyw/',

                'notes' => null,

            ],

            [

                'symbol' => 'UBEW',

                'fund_name' => 'Roundhill UBER WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-05-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/ubew/',

                'notes' => null,

            ],

            [

                'symbol' => 'UNHW',

                'fund_name' => 'Roundhill UNH WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.99,

                'inception_date' => '2025-05-08',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/unhw/',

                'notes' => null,

            ],

            [

                'symbol' => 'WEEK',

                'fund_name' => 'Roundhill WeeklyPay ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.75,

                'inception_date' => '2025-01-16',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/week/',

                'notes' => 'Portfolio strategy ETF',

            ],

            [

                'symbol' => 'XDTE',

                'fund_name' => 'Roundhill S&P 500 0DTE Covered Call Strategy ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::WEEKLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.95,

                'inception_date' => '2024-03-07',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/xdte/',

                'notes' => '0DTE strategy ETF',

            ],

            [

                'symbol' => 'XPAY',

                'fund_name' => 'Roundhill X-Pay Income ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::MONTHLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.59,

                'inception_date' => '2025-01-23',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/xpay/',

                'notes' => 'Income strategy ETF',

            ],

            [

                'symbol' => 'YBTC',

                'fund_name' => 'Roundhill Bitcoin Covered Call Strategy ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::MONTHLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.95,

                'inception_date' => '2024-01-18',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/ybtc/',

                'notes' => 'Bitcoin strategy ETF',

            ],

            [

                'symbol' => 'YETH',

                'fund_name' => 'Roundhill Ether Covered Call Strategy ETF',

                'etf_issuer_id' => EtfIssuer::ROUNDHILL,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::MONTHLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.95,

                'inception_date' => '2025-03-20',

                'source' => 'official_issuer_page',

                'website_url' => 'https://www.roundhillinvestments.com/etf/yeth/',

                'notes' => 'Ethereum strategy ETF',

            ],
            [
                'symbol' => 'FEPI',
                'fund_name' => 'REX FANG & Innovation Equity Premium Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.65,
                'inception_date' => '2023-10-11',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/fepi/',
                'notes' => null,
            ],

            [
                'symbol' => 'AIPI',
                'fund_name' => 'REX AI Equity Premium Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.65,
                'inception_date' => '2024-06-04',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/aipi/',
                'notes' => 'AI-focused income ETF',
            ],

            [
                'symbol' => 'CEPI',
                'fund_name' => 'REX Crypto Equity Premium Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.85,
                'inception_date' => '2024-09-10',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/cepi/',
                'notes' => 'Crypto-focused income ETF',
            ],

            [
                'symbol' => 'TSII',
                'fund_name' => 'REX TSLA Weekly Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2025-04-02',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/tsii/',
                'notes' => null,
            ],

            [
                'symbol' => 'WMTI',
                'fund_name' => 'REX Walmart Weekly Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2025-04-02',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/wmti/',
                'notes' => null,
            ],

            [
                'symbol' => 'COII',
                'fund_name' => 'REX COIN Weekly Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2025-04-02',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/coii/',
                'notes' => null,
            ],

            [
                'symbol' => 'MSII',
                'fund_name' => 'REX MSTR Weekly Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2025-04-02',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/msii/',
                'notes' => null,
            ],

            [
                'symbol' => 'HOII',
                'fund_name' => 'REX HOOD Weekly Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2025-04-02',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/hoii/',
                'notes' => null,
            ],

            [
                'symbol' => 'PLTI',
                'fund_name' => 'REX PLTR Weekly Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2025-04-02',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/plti/',
                'notes' => null,
            ],

            [
                'symbol' => 'CWII',
                'fund_name' => 'REX CRWD Weekly Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2025-04-02',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/cwii/',
                'notes' => null,
            ],

            [
                'symbol' => 'LLII',
                'fund_name' => 'REX LLY Weekly Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2025-04-02',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/llii/',
                'notes' => null,
            ],

            [
                'symbol' => 'GIF',
                'fund_name' => 'REX Golden Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.75,
                'inception_date' => '2025-02-06',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/gif/',
                'notes' => 'Multi-asset income ETF',
            ],

            [
                'symbol' => 'ULTI',
                'fund_name' => 'REX Ultra Income ETF',
                'etf_issuer_id' => EtfIssuer::REX,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.85,
                'inception_date' => '2025-03-27',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.rexshares.com/ulti/',
                'notes' => 'Diversified income strategy ETF',
            ],
            [

                'symbol' => 'JEPI',

                'fund_name' => 'JPMorgan Equity Premium Income ETF',

                'etf_issuer_id' => EtfIssuer::JPMORGAN,

                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,

                'distribution_frequency_id' => DistributionFrequency::MONTHLY,

                'status_id' => Status::ACTIVE,

                'expense_ratio' => 0.35,

                'inception_date' => '2020-05-20',

                'source' => 'official_issuer_page',

                'website_url' => 'https://am.jpmorgan.com/us/en/asset-management/adv/products/jpmorgan-equity-premium-income-etf-etf-shares-46641q3323',

                'notes' => null,

            ],

            [
                'symbol' => 'EDGQ',
                'fund_name' => 'Global X S&P 500 Quality Dividend Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.60,
                'inception_date' => '2024-06-25',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/edgq/',
                'notes' => null,
            ],

            [
                'symbol' => 'EDGX',
                'fund_name' => 'Global X S&P 500 Covered Call & Growth ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.60,
                'inception_date' => '2024-06-25',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/edgx/',
                'notes' => null,
            ],

            [
                'symbol' => 'QYLD',
                'fund_name' => 'Global X Nasdaq 100 Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.60,
                'inception_date' => '2013-12-11',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/qyld/',
                'notes' => null,
            ],

            [
                'symbol' => 'XYLD',
                'fund_name' => 'Global X S&P 500 Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.60,
                'inception_date' => '2013-06-21',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/xyld/',
                'notes' => null,
            ],

            [
                'symbol' => 'RYLD',
                'fund_name' => 'Global X Russell 2000 Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.60,
                'inception_date' => '2019-04-17',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/ryld/',
                'notes' => null,
            ],

            [
                'symbol' => 'DJIA',
                'fund_name' => 'Global X Dow 30 Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.45,
                'inception_date' => '2022-08-25',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/djia/',
                'notes' => null,
            ],

            [
                'symbol' => 'XCLR',
                'fund_name' => 'Global X S&P 500 Collar 95-110 ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::COLLAR,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.60,
                'inception_date' => '2024-08-27',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/xclr/',
                'notes' => 'Collar strategy ETF',
            ],

            [
                'symbol' => 'QCLR',
                'fund_name' => 'Global X Nasdaq 100 Collar 95-110 ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::COLLAR,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.60,
                'inception_date' => '2024-08-27',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/qclr/',
                'notes' => 'Collar strategy ETF',
            ],

            [
                'symbol' => 'QYLG',
                'fund_name' => 'Global X Nasdaq 100 Covered Call & Growth ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.60,
                'inception_date' => '2020-09-18',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/qylg/',
                'notes' => null,
            ],

            [
                'symbol' => 'XYLG',
                'fund_name' => 'Global X S&P 500 Covered Call & Growth ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.60,
                'inception_date' => '2020-09-18',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/xylg/',
                'notes' => null,
            ],

            [
                'symbol' => 'RYLG',
                'fund_name' => 'Global X Russell 2000 Covered Call & Growth ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.60,
                'inception_date' => '2022-09-21',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/rylg/',
                'notes' => null,
            ],
            [
                'symbol' => 'SDIV',
                'fund_name' => 'Global X SuperDividend ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::DIVIDEND_GROWTH,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.58,
                'inception_date' => '2011-06-08',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/sdiv/',
                'notes' => 'Global high dividend ETF',
            ],

            [
                'symbol' => 'DIV',
                'fund_name' => 'Global X SuperDividend U.S. ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::DIVIDEND_GROWTH,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.45,
                'inception_date' => '2013-03-12',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/div/',
                'notes' => 'U.S. high dividend ETF',
            ],

            [
                'symbol' => 'SRET',
                'fund_name' => 'Global X SuperDividend REIT ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::DIVIDEND_GROWTH,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.58,
                'inception_date' => '2015-03-17',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/sret/',
                'notes' => 'REIT dividend ETF',
            ],

            [
                'symbol' => 'SDEM',
                'fund_name' => 'Global X MSCI SuperDividend Emerging Markets ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::DIVIDEND_GROWTH,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.58,
                'inception_date' => '2015-03-17',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/sdem/',
                'notes' => 'Emerging markets dividend ETF',
            ],

            [
                'symbol' => 'EFAS',
                'fund_name' => 'Global X MSCI SuperDividend EAFE ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::DIVIDEND_GROWTH,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.56,
                'inception_date' => '2014-08-28',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/efas/',
                'notes' => 'International dividend ETF',
            ],

            [
                'symbol' => 'ALTY',
                'fund_name' => 'Global X Alternative Income ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::ALTERNATIVE_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 1.14,
                'inception_date' => '2015-07-14',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/alty/',
                'notes' => 'Alternative income strategy ETF',
            ],

            [
                'symbol' => 'QDIV',
                'fund_name' => 'Global X S&P 500 Quality Dividend ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::DIVIDEND_GROWTH,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.20,
                'inception_date' => '2018-05-01',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/qdiv/',
                'notes' => 'Quality dividend ETF',
            ],

            [
                'symbol' => 'CLIP',
                'fund_name' => 'Global X 1-3 Month T-Bill ETF',
                'etf_issuer_id' => EtfIssuer::GLOBAL_X,
                'etf_strategy_type_id' => EtfStrategyType::TREASURY_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.07,
                'inception_date' => '2023-06-14',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.globalxetfs.com/funds/clip/',
                'notes' => 'Treasury bill ETF',
            ],
            [
                'symbol' => 'QQQY',
                'fund_name' => 'Defiance Nasdaq 100 Enhanced Option Income ETF',
                'etf_issuer_id' => EtfIssuer::DEFIANCE,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2024-01-04',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.defianceetfs.com/qqqy/',
                'notes' => '0DTE enhanced income ETF',
            ],

            [
                'symbol' => 'WDTE',
                'fund_name' => 'Defiance S&P 500 Enhanced Option Income ETF',
                'etf_issuer_id' => EtfIssuer::DEFIANCE,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2024-09-19',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.defianceetfs.com/wdte/',
                'notes' => '0DTE enhanced income ETF',
            ],

            [
                'symbol' => 'IWMY',
                'fund_name' => 'Defiance Russell 2000 Enhanced Option Income ETF',
                'etf_issuer_id' => EtfIssuer::DEFIANCE,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2023-09-20',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.defianceetfs.com/iwmy/',
                'notes' => '0DTE enhanced income ETF',
            ],

            [
                'symbol' => 'SPYT',
                'fund_name' => 'Defiance S&P 500 Target Income ETF',
                'etf_issuer_id' => EtfIssuer::DEFIANCE,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.94,
                'inception_date' => '2025-04-03',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.defianceetfs.com/spyt/',
                'notes' => 'Target income ETF',
            ],

            [
                'symbol' => 'QQQT',
                'fund_name' => 'Defiance Nasdaq 100 Target Income ETF',
                'etf_issuer_id' => EtfIssuer::DEFIANCE,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::WEEKLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.94,
                'inception_date' => '2025-04-03',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.defianceetfs.com/qqqt/',
                'notes' => 'Target income ETF',
            ],

            [
                'symbol' => 'QLDY',
                'fund_name' => 'Defiance Nasdaq 100 Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::DEFIANCE,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.88,
                'inception_date' => '2024-07-18',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.defianceetfs.com/qldy/',
                'notes' => null,
            ],

            [
                'symbol' => 'USOY',
                'fund_name' => 'Defiance Oil Enhanced Option Income ETF',
                'etf_issuer_id' => EtfIssuer::DEFIANCE,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 1.29,
                'inception_date' => '2023-11-29',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.defianceetfs.com/usoy/',
                'notes' => 'Oil income ETF',
            ],

            [
                'symbol' => 'GLDY',
                'fund_name' => 'Defiance Gold Enhanced Option Income ETF',
                'etf_issuer_id' => EtfIssuer::DEFIANCE,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 1.19,
                'inception_date' => '2024-06-06',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.defianceetfs.com/gldy/',
                'notes' => 'Gold income ETF',
            ],

            [
                'symbol' => 'YBMN',
                'fund_name' => 'Defiance Bitcoin Enhanced Option Income ETF',
                'etf_issuer_id' => EtfIssuer::DEFIANCE,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 1.29,
                'inception_date' => '2024-02-28',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.defianceetfs.com/ybmn/',
                'notes' => 'Bitcoin income ETF',
            ],

            [
                'symbol' => 'MST',
                'fund_name' => 'Defiance Daily Target 2X Long MSTR ETF',
                'etf_issuer_id' => EtfIssuer::DEFIANCE,
                'etf_strategy_type_id' => EtfStrategyType::LEVERAGED,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 1.29,
                'inception_date' => '2025-03-06',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.defianceetfs.com/mst/',
                'notes' => 'Leveraged ETF',
            ],
            [
                'symbol' => 'SPYI',
                'fund_name' => 'NEOS S&P 500 High Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.68,
                'inception_date' => '2022-08-29',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/spyi/',
                'notes' => null,
            ],

            [
                'symbol' => 'IWMI',
                'fund_name' => 'NEOS Russell 2000 High Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.68,
                'inception_date' => '2023-06-20',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/iwmi/',
                'notes' => null,
            ],

            [
                'symbol' => 'NIHI',
                'fund_name' => 'NEOS Nasdaq 100 High Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.68,
                'inception_date' => '2024-10-15',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/nihi/',
                'notes' => 'Enhanced income ETF',
            ],

            [
                'symbol' => 'XSPI',
                'fund_name' => 'NEOS S&P 500 High Income ETF - Tax Efficient',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.68,
                'inception_date' => '2024-06-18',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/xspi/',
                'notes' => 'Tax-efficient income ETF',
            ],

            [
                'symbol' => 'XQQI',
                'fund_name' => 'NEOS Nasdaq 100 High Income ETF - Tax Efficient',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.68,
                'inception_date' => '2024-06-18',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/xqqi/',
                'notes' => 'Tax-efficient income ETF',
            ],

            [
                'symbol' => 'XBCI',
                'fund_name' => 'NEOS Bitcoin High Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.98,
                'inception_date' => '2024-11-19',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/xbci/',
                'notes' => 'Bitcoin income ETF',
            ],

            [
                'symbol' => 'QQQH',
                'fund_name' => 'NEOS Nasdaq 100 Hedged Equity Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::HEDGED_EQUITY,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.58,
                'inception_date' => '2024-03-26',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/qqqh/',
                'notes' => 'Hedged equity ETF',
            ],

            [
                'symbol' => 'SPYH',
                'fund_name' => 'NEOS S&P 500 Hedged Equity Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::HEDGED_EQUITY,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.58,
                'inception_date' => '2024-03-26',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/spyh/',
                'notes' => 'Hedged equity ETF',
            ],

            [
                'symbol' => 'NLSI',
                'fund_name' => 'NEOS Enhanced Income 1-3 Month T-Bill ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::TREASURY_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.38,
                'inception_date' => '2023-07-18',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/nlsi/',
                'notes' => 'Treasury income ETF',
            ],
            [
                'symbol' => 'BTCI',
                'fund_name' => 'NEOS Bitcoin High Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.98,
                'inception_date' => '2024-09-17',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/btci/',
                'notes' => 'Bitcoin income ETF',
            ],

            [
                'symbol' => 'NEHI',
                'fund_name' => 'NEOS Enhanced Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.68,
                'inception_date' => '2024-10-15',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/nehi/',
                'notes' => 'Enhanced income strategy ETF',
            ],

            [
                'symbol' => 'IYRI',
                'fund_name' => 'NEOS Real Estate High Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.68,
                'inception_date' => '2024-11-12',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/iyri/',
                'notes' => 'Real estate income ETF',
            ],

            [
                'symbol' => 'IAUI',
                'fund_name' => 'NEOS Gold Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.68,
                'inception_date' => '2024-11-12',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/iaui/',
                'notes' => 'Gold income ETF',
            ],

            [
                'symbol' => 'MLPI',
                'fund_name' => 'NEOS MLP & Energy Infrastructure Income ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::ENERGY_INFRASTRUCTURE,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.85,
                'inception_date' => '2024-10-22',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/mlpi/',
                'notes' => 'MLP income ETF',
            ],

            [
                'symbol' => 'CSHI',
                'fund_name' => 'NEOS Enhanced Income Cash Alternative ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::CASH_ALTERNATIVE,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.38,
                'inception_date' => '2022-12-13',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/cshi/',
                'notes' => 'Cash alternative ETF',
            ],

            [
                'symbol' => 'BNDI',
                'fund_name' => 'NEOS Enhanced Income Aggregate Bond ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::BOND,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.58,
                'inception_date' => '2023-09-19',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/bndi/',
                'notes' => 'Bond income ETF',
            ],

            [
                'symbol' => 'HYBI',
                'fund_name' => 'NEOS Enhanced Income High Yield Bond ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::HIGH_YIELD_BOND,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.68,
                'inception_date' => '2024-02-27',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/hybi/',
                'notes' => 'High yield bond ETF',
            ],

            [
                'symbol' => 'TLTI',
                'fund_name' => 'NEOS Enhanced Income 20+ Year Treasury Bond ETF',
                'etf_issuer_id' => EtfIssuer::NEOS,
                'etf_strategy_type_id' => EtfStrategyType::TREASURY_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.58,
                'inception_date' => '2024-02-27',
                'source' => 'official_issuer_page',
                'website_url' => 'https://neosfunds.com/tlti/',
                'notes' => 'Treasury bond income ETF',
            ],

            [
                'symbol' => 'KQQQ',
                'fund_name' => 'Kurv Nasdaq-100 Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2024-01-11',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/kqqq',
                'notes' => null,
            ],

            [
                'symbol' => 'KYLD',
                'fund_name' => 'Kurv Yield Premium Strategy ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2024-03-14',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/kyld',
                'notes' => null,
            ],

            [
                'symbol' => 'KGLD',
                'fund_name' => 'Kurv Gold Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.89,
                'inception_date' => '2024-05-09',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/kgld',
                'notes' => 'Gold strategy ETF',
            ],

            [
                'symbol' => 'KSLV',
                'fund_name' => 'Kurv Silver Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.89,
                'inception_date' => '2024-05-09',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/kslv',
                'notes' => 'Silver strategy ETF',
            ],

            [
                'symbol' => 'KCOP',
                'fund_name' => 'Kurv Copper Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.89,
                'inception_date' => '2024-07-11',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/kcop',
                'notes' => 'Copper strategy ETF',
            ],

            [
                'symbol' => 'AMZP',
                'fund_name' => 'Kurv Amazon Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2024-01-11',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/amzp',
                'notes' => null,
            ],

            [
                'symbol' => 'AAPY',
                'fund_name' => 'Kurv Apple Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2024-01-11',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/aapy',
                'notes' => null,
            ],

            [
                'symbol' => 'GOOP',
                'fund_name' => 'Kurv Alphabet Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2024-01-11',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/goop',
                'notes' => null,
            ],

            [
                'symbol' => 'MSFY',
                'fund_name' => 'Kurv Microsoft Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2024-01-11',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/msfy',
                'notes' => null,
            ],

            [
                'symbol' => 'NFLP',
                'fund_name' => 'Kurv Netflix Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2024-01-11',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/nflp',
                'notes' => null,
            ],

            [
                'symbol' => 'TSLP',
                'fund_name' => 'Kurv Tesla Covered Call ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::COVERED_CALL,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.99,
                'inception_date' => '2024-01-11',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/tslp',
                'notes' => null,
            ],

            [
                'symbol' => 'LQID',
                'fund_name' => 'Kurv Liquidity Premium Strategy ETF',
                'etf_issuer_id' => EtfIssuer::KURV,
                'etf_strategy_type_id' => EtfStrategyType::CASH_ALTERNATIVE,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.45,
                'inception_date' => '2024-08-15',
                'source' => 'official_issuer_page',
                'website_url' => 'https://www.kurvinvest.com/etf/lqid',
                'notes' => 'Cash alternative ETF',
            ],

            [
                'symbol' => 'FIAX',
                'fund_name' => 'Nicholas Fixed Income Alternative ETF',
                'etf_issuer_id' => EtfIssuer::NICHOLASX,
                'etf_strategy_type_id' => EtfStrategyType::ALTERNATIVE_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.95,
                'inception_date' => '2022-11-29',
                'source' => 'official_issuer_page',
                'website_url' => 'https://nicholasx.com/fiax/',
                'notes' => 'Fixed income alternative ETF',
            ],

            [
                'symbol' => 'GIAX',
                'fund_name' => 'Nicholas Global Income Alternative ETF',
                'etf_issuer_id' => EtfIssuer::NICHOLASX,
                'etf_strategy_type_id' => EtfStrategyType::ALTERNATIVE_INCOME,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.95,
                'inception_date' => '2024-06-25',
                'source' => 'official_issuer_page',
                'website_url' => 'https://nicholasx.com/giax/',
                'notes' => 'Global alternative income ETF',
            ],

            [
                'symbol' => 'SLVX',
                'fund_name' => 'Nicholas Silver Income ETF',
                'etf_issuer_id' => EtfIssuer::NICHOLASX,
                'etf_strategy_type_id' => EtfStrategyType::PRECIOUS_METALS,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.95,
                'inception_date' => '2025-01-28',
                'source' => 'official_issuer_page',
                'website_url' => 'https://nicholasx.com/slvx/',
                'notes' => 'Silver income ETF',
            ],

            [
                'symbol' => 'GLDN',
                'fund_name' => 'Nicholas Gold Income ETF',
                'etf_issuer_id' => EtfIssuer::NICHOLASX,
                'etf_strategy_type_id' => EtfStrategyType::PRECIOUS_METALS,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.95,
                'inception_date' => '2025-01-28',
                'source' => 'official_issuer_page',
                'website_url' => 'https://nicholasx.com/gldn/',
                'notes' => 'Gold income ETF',
            ],

            [
                'symbol' => 'NUKX',
                'fund_name' => 'Nicholas Uranium & Nuclear Energy Income ETF',
                'etf_issuer_id' => EtfIssuer::NICHOLASX,
                'etf_strategy_type_id' => EtfStrategyType::ENERGY_INFRASTRUCTURE,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 1.05,
                'inception_date' => '2025-03-25',
                'source' => 'official_issuer_page',
                'website_url' => 'https://nicholasx.com/nukx/',
                'notes' => 'Nuclear energy ETF',
            ],

            [
                'symbol' => 'WEPN',
                'fund_name' => 'Nicholas Defense & Aerospace Income ETF',
                'etf_issuer_id' => EtfIssuer::NICHOLASX,
                'etf_strategy_type_id' => EtfStrategyType::DEFENSE,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 1.05,
                'inception_date' => '2025-03-25',
                'source' => 'official_issuer_page',
                'website_url' => 'https://nicholasx.com/wepn/',
                'notes' => 'Defense sector ETF',
            ],

            [
                'symbol' => 'NGHT',
                'fund_name' => 'Nicholas Overnight Risk Managed ETF',
                'etf_issuer_id' => EtfIssuer::NICHOLASX,
                'etf_strategy_type_id' => EtfStrategyType::RISK_MANAGED,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.89,
                'inception_date' => '2024-09-24',
                'source' => 'official_issuer_page',
                'website_url' => 'https://nicholasx.com/nght/',
                'notes' => 'Risk-managed strategy ETF',
            ],

            [
                'symbol' => 'BHDG',
                'fund_name' => 'Nicholas Black Swan Hedged Equity ETF',
                'etf_issuer_id' => EtfIssuer::NICHOLASX,
                'etf_strategy_type_id' => EtfStrategyType::HEDGED_EQUITY,
                'distribution_frequency_id' => DistributionFrequency::MONTHLY,
                'status_id' => Status::ACTIVE,
                'expense_ratio' => 0.95,
                'inception_date' => '2025-02-18',
                'source' => 'official_issuer_page',
                'website_url' => 'https://nicholasx.com/bhdg/',
                'notes' => 'Tail-risk hedged ETF',
            ],

        ];

        $now = now();

        $etfs = array_map(

            function ($etf) use ($now) {

                $etf['created_at'] = $now;

                $etf['updated_at'] = $now;

                return $etf;
            },

            $etfs

        );

        Etf::insert($etfs);
    }
}
