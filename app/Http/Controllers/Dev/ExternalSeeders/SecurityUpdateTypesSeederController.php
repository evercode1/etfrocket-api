<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\SecurityUpdateType;

class SecurityUpdateTypesSeederController extends Controller
{
    public function run(): void
    {
        SecurityUpdateType::truncate();

        $securityUpdateTypes = [

            'Dividend',
            'Fund Data',

        ];

        foreach ($securityUpdateTypes as $securityUpdateTypeName) {

            SecurityUpdateType::create([

                'security_update_type_name' => $securityUpdateTypeName,

            ]);
        }
    }
}
