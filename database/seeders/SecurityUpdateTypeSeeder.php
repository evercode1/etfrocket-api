<?php

namespace Database\Seeders;

use App\Models\SecurityUpdateType;
use Illuminate\Database\Seeder;

class SecurityUpdateTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SecurityUpdateType::truncate();

        $values = [

            'Dividend',
            'Fund Data',

        ];

        foreach ($values as $value) {
            SecurityUpdateType::create([
                'security_update_type_name' => $value,
            ]);
        }
    }
}
