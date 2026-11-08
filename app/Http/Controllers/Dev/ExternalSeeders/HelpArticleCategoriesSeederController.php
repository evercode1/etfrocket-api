<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\HelpArticleCategory;
use Illuminate\Support\Str;

class HelpArticleCategoriesSeederController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Help center categories
    |--------------------------------------------------------------------------
    |
    | Listed in display order. Names must match the 'category' values used
    | in HelpArticlesSeederController.
    |
    */

    public const CATEGORIES = [
        'Getting Started',
        'AUM Leaderboard',
        'ETF Pages & Charts',
        'Data & Methodology',
        'Coverage',
        'Troubleshooting',
        'About ETF Rocket',
    ];

    public function run(): void
    {
        if (! env('ALLOW_SEEDS')) {
            return;
        }

        HelpArticleCategory::truncate();

        foreach (self::CATEGORIES as $index => $categoryName) {
            HelpArticleCategory::updateOrCreate(
                [
                    'slug' => Str::slug($categoryName),
                ],
                [
                    'category_name' => $categoryName,
                    'sort_order' => $index + 1,
                    'is_active' => 1,
                ]
            );
        }
    }
}
