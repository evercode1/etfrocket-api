<?php

namespace Database\Seeders;

use App\Http\Controllers\Dev\ExternalSeeders\SecurityDetailsSeederController;
use Illuminate\Database\Seeder;

class SecurityDetailsSeeder extends Seeder
{
    public function run(): void
    {
        app(
            SecurityDetailsSeederController::class
        )->run();
    }
}