<?php

namespace Database\Seeders;

use App\Http\Controllers\Dev\ExternalSeeders\HelpArticleCategoriesSeederController;
use Illuminate\Database\Seeder;

class HelpArticleCategorySeeder extends Seeder
{
    /*
    | Categories are defined once, in HelpArticleCategoriesSeederController
    | (also used by the /make-seed endpoint). Requires ALLOW_SEEDS.
    */
    public function run(): void
    {
        app(HelpArticleCategoriesSeederController::class)->run();
    }
}
