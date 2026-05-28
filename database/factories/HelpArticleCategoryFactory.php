<?php

namespace Database\Factories;

use App\Models\HelpArticleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HelpArticleCategory>
 */
class HelpArticleCategoryFactory extends Factory
{
    protected $model = HelpArticleCategory::class;

    public function definition(): array
    {
        $categoryName = fake()->unique()->randomElement([

            'Getting Started',
            'Accounts',
            'Portfolios',
            'ETF Data',
            'Dividends',
            'Imports',
            'Support',
            'Billing',
            'Troubleshooting',
            'Analytics',

        ]);

        return [

            'category_name' => $categoryName,

            'slug' => Str::slug($categoryName),

            'sort_order' => fake()->numberBetween(1, 100),

            'is_active' => fake()->boolean(90),

        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [

            'is_active' => 1,

        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [

            'is_active' => 0,

        ]);
    }
}
