<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\ImportType;

class ImportTypesSeederController extends Controller
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

            'Calculate Security Metrics',

            'Security Price Import',

            'Security NAV Import',

            'Security AUM Import',

            'Security Dividend Import',

            'Portfolio Import',

            'Data Integrity Audit',

            'Scheduled Security Updates',

        ];

        foreach ($values as $value) {

            ImportType::create([

                'import_type_name' => $value,

            ]);
        }
    }
}
