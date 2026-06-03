<?php

namespace Database\Seeders;

use App\Models\SignalType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SignalTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('signal_types')
            ->truncate();

        $values = [

            'market snapshot',

            'market conditions',

            'market events',

            'etf watchlist',

        ];

        foreach ($values as $value) {

            SignalType::create([

                'signal_type_name' => $value,

            ]);
        }
    }
}
