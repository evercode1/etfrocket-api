<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\SignalType;

class SignalTypesSeederController extends Controller
{
    public function run(): void
    {

        SignalType::truncate();

        $values = [

            'market snapshot',

            'market conditions',

            'market events'

        ];

        foreach ($values as $value) {

            SignalType::create([

                'signal_type_name' =>
                $value,

            ]);
        }
    }
}
