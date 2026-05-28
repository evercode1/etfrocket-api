<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\SecurityType;

class SecurityTypesSeederController extends Controller
{
    public function run(): void
    {
        SecurityType::truncate();

        $securityTypes = [

            'ETF',
            'Stock',
            'Index',
            'Crypto',
            'Forex',
            'Bond',
            'Mutual Fund',
            'Option',
            'Future',

        ];

        foreach ($securityTypes as $securityType) {

            SecurityType::create([

                'security_type_name' => $securityType,

            ]);
        }
    }
}
