<?php

namespace Database\Seeders;

use App\Http\Controllers\Dev\ExternalSeeders\HelpArticlesSeederController;
use Illuminate\Database\Seeder;

class HelpArticleSeeder extends Seeder
{
    /*
    | Articles are defined once, in HelpArticlesSeederController (also used
    | by the /make-seed endpoint). Run HelpArticleCategorySeeder first.
    | Requires ALLOW_SEEDS.
    */
    public function run(): void
    {
        app(HelpArticlesSeederController::class)->run();
    }
}
