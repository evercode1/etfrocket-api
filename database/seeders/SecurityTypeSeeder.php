<?php

namespace Database\Seeders;

use App\Models\SecurityType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SecurityTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('security_types')->truncate();

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

        foreach (
            $securityTypes as $securityType
        ) {

            SecurityType::create([
                'security_type_name' => $securityType,
            ]);
        }
    }
}
