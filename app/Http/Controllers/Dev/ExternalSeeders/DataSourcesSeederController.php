<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\DataSource;
use App\Models\Status;
use Illuminate\Support\Facades\DB;

class DataSourcesSeederController extends Controller
{
    public function run(): void
    {
        DB::table('data_sources')->truncate();

        $sources = [

            [
                'data_source_name' => 'Manual Entry',
                'website_url' => null,
            ],

            [
                'data_source_name' => 'YieldMax Website',
                'website_url' => 'https://yieldmaxetfs.com',
            ],

            [
                'data_source_name' => 'Roundhill Website',
                'website_url' => 'https://www.roundhillinvestments.com',
            ],

            [
                'data_source_name' => 'REX Shares Website',
                'website_url' => 'https://www.rexshares.com',
            ],

            [
                'data_source_name' => 'EODHD API',
                'website_url' => 'https://eodhd.com',
            ],

            [
                'data_source_name' => 'Tiingo API',
                'website_url' => 'https://www.tiingo.com',
            ],

            [
                'data_source_name' => 'FINRA',
                'website_url' => 'https://www.finra.org',
            ],

            [
                'data_source_name' => 'Nasdaq',
                'website_url' => 'https://www.nasdaq.com',
            ],

            [
                'data_source_name' => 'Yahoo Finance',
                'website_url' => 'https://finance.yahoo.com',
            ],

            [
                'data_source_name' => 'FMP API',
                'website_url' => 'https://site.financialmodelingprep.com',
            ],

            [
                'data_source_name' => 'Seeking Alpha',
                'website_url' => 'https://seekingalpha.com',
            ],

            [
                'data_source_name' => 'AI Scraper',
                'website_url' => null,
            ],

            [
                'data_source_name' => 'Twelve Data API',
                'website_url' => 'https://twelvedata.com',
            ],

            [
                'data_source_name' => 'Web Scraper',
                'website_url' => null,
            ],

        ];

        foreach ($sources as $source) {

            DataSource::create([

                'data_source_name' => $source['data_source_name'],

                'website_url' => $source['website_url'],

                'status_id' => Status::ACTIVE,

                'notes' => null,

            ]);
        }
    }
}
