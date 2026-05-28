<?php

namespace Database\Seeders;

use App\Http\Controllers\Dev\ExternalSeeders\SecuritiesSeederController;
use Illuminate\Database\Seeder;

class SecuritiesSeeder extends Seeder
{
    public function run(): void
    {
        app(
            SecuritiesSeederController::class
        )->run();
    }
}