<?php

namespace Tests\Unit\HelpArticles;

use App\Http\Controllers\Dev\ExternalSeeders\HelpArticleCategoriesSeederController;
use App\Http\Controllers\Dev\ExternalSeeders\HelpArticlesSeederController;
use Illuminate\Support\Str;
use Tests\TestCase;

class HelpArticleSeedContentTest extends TestCase
{
    private function articles(): array
    {
        return (new HelpArticlesSeederController)->articles();
    }

    public function test_every_article_uses_a_seeded_category(): void
    {
        $categories = HelpArticleCategoriesSeederController::CATEGORIES;

        foreach ($this->articles() as $article) {
            $this->assertContains($article['category'], $categories, $article['title']);
        }
    }

    public function test_every_category_has_at_least_one_article(): void
    {
        $used = array_unique(array_column($this->articles(), 'category'));

        foreach (HelpArticleCategoriesSeederController::CATEGORIES as $category) {
            $this->assertContains($category, $used, "No articles in {$category}");
        }
    }

    public function test_article_slugs_are_unique(): void
    {
        $slugs = array_map(fn ($a) => Str::slug($a['title']), $this->articles());

        $this->assertSame(count($slugs), count(array_unique($slugs)));
    }

    public function test_internal_help_links_point_to_existing_articles(): void
    {
        $slugs = array_map(fn ($a) => Str::slug($a['title']), $this->articles());

        foreach ($this->articles() as $article) {
            preg_match_all('#href="/help/([^"]+)"#', $article['content'], $matches);

            foreach ($matches[1] as $linkedSlug) {
                $this->assertContains($linkedSlug, $slugs, "{$article['title']} links to missing /help/{$linkedSlug}");
            }
        }
    }
}
