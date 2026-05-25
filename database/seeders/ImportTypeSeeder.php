<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ImportType;

class ImportTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ImportType::truncate();

        $values = [

            'AI Data Extraction',

            'Market Snapshot',

            'Market Conditions',

            'Market Events',

            'Calculate ETF Metrics',

            'ETF Price Import',

            'ETF NAV Import',

            'ETF AUM Import',

            'ETF Dividend Import',

            'Portfolio Import',

            'Data Integrity Audit',

        ];

        foreach ($values as $value) {

            ImportType::create([

                'import_type_name' =>

                $value,

            ]);
        }
    }
}
