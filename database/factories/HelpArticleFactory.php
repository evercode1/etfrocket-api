<?php

namespace Database\Factories;

use App\Models\HelpArticle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HelpArticle>
 */
class HelpArticleFactory extends Factory
{
    protected $model = HelpArticle::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(rand(3, 6));

        return [

            'title' => $title,

            'slug' => Str::slug($title),

            'help_article_category_id' => rand(1, 5),

            'summary' => fake()->paragraph(),

            'content' => collect(fake()->paragraphs(rand(4, 10)))

                ->map(fn ($paragraph) => "<p>{$paragraph}</p>")

                ->implode("\n\n"),

            'is_published' => fake()->boolean(80),

        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [

            'is_published' => 1,

        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => [

            'is_published' => 0,

        ]);
    }
}
