<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\EtfIssuer;
use App\Models\Status;
use Illuminate\Support\Facades\DB;

class EtfIssuersSeederController extends Controller
{

    public function run(): void
    {
        DB::table('etf_issuers')->truncate();

        $issuers = [

            [
                'etf_issuer_name' => 'YieldMax',
                'website_url' => 'https://yieldmaxetfs.com',
            ],

            [
                'etf_issuer_name' => 'Roundhill Investments',
                'website_url' => 'https://www.roundhillinvestments.com',
            ],

            [
                'etf_issuer_name' => 'REX Shares',
                'website_url' => 'https://www.rexshares.com',
            ],

            [
                'etf_issuer_name' => 'JPMorgan',
                'website_url' => 'https://am.jpmorgan.com',
            ],

            [
                'etf_issuer_name' => 'Global X',
                'website_url' => 'https://www.globalxetfs.com',
            ],

            [
                'etf_issuer_name' => 'Defiance ETFs',
                'website_url' => 'https://www.defianceetfs.com',
            ],

            [
                'etf_issuer_name' => 'Amplify ETFs',
                'website_url' => 'https://amplifyetfs.com',
            ],

            [
                'etf_issuer_name' => 'Simplify Asset Management',
                'website_url' => 'https://www.simplify.us',
            ],

            [
                'etf_issuer_name' => 'NEOS Investments',
                'website_url' => 'https://neosfunds.com',
            ],

            [
                'etf_issuer_name' => 'Kurv Investment Management',
                'website_url' => 'https://kurvinvest.com',
            ],

            [
                'etf_issuer_name' => 'NicholasX',
                'website_url' => 'https://www.nicholasx.com',
            ],
            [
                'etf_issuer_name' => 'TappAlpha',
                'website_url' => 'https://www.tappalphafunds.com',
            ],

        ];

        foreach ($issuers as $issuer) {

            EtfIssuer::create([

                'etf_issuer_name' => $issuer['etf_issuer_name'],

                'website_url' => $issuer['website_url'],

                'status_id' => Status::ACTIVE,

                'notes' => $issuer['etf_issuer_name'],

            ]);
        }
    }
}
