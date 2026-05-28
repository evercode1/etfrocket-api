<?php

namespace Tests\Feature\HelpArticles;

use App\Models\HelpArticle;
use App\Models\HelpArticleCategory;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HelpArticlesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('help_articles')->truncate();
        DB::table('help_article_categories')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('help_articles')->truncate();
        DB::table('help_article_categories')->truncate();

        parent::tearDown();
    }

    public function test_it_lists_published_help_articles(): void
    {
        $portfolioCategory = HelpArticleCategory::factory()->create([
            'category_name' => 'Portfolios',
            'slug' => 'portfolios',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $supportCategory = HelpArticleCategory::factory()->create([
            'category_name' => 'Support',
            'slug' => 'support',
            'sort_order' => 2,
            'is_active' => 1,
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $portfolioCategory->id,
            'title' => 'Creating Your First Portfolio',
            'slug' => 'creating-your-first-portfolio',
            'summary' => 'Learn how to create a portfolio.',
            'content' => 'Portfolio help content.',
            'is_published' => 1,
            'created_at' => now()->subDay(),
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $supportCategory->id,
            'title' => 'Hidden Draft Article',
            'slug' => 'hidden-draft-article',
            'summary' => 'Draft article.',
            'content' => 'Draft content.',
            'is_published' => 0,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/help-articles');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'articles.data')
            ->assertJsonPath('articles.data.0.title', 'Creating Your First Portfolio')
            ->assertJsonPath('articles.data.0.slug', 'creating-your-first-portfolio')
            ->assertJsonPath('articles.data.0.category_name', 'Portfolios')
            ->assertJsonPath('articles.data.0.category_slug', 'portfolios')
            ->assertJsonCount(1, 'categories')
            ->assertJsonPath('categories.0.category_name', 'Portfolios')
            ->assertJsonPath('categories.0.slug', 'portfolios');
    }

    public function test_it_filters_help_articles_by_category_slug(): void
    {
        $portfolioCategory = HelpArticleCategory::factory()->create([
            'category_name' => 'Portfolios',
            'slug' => 'portfolios',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $accountCategory = HelpArticleCategory::factory()->create([
            'category_name' => 'Accounts',
            'slug' => 'accounts',
            'sort_order' => 2,
            'is_active' => 1,
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $portfolioCategory->id,
            'title' => 'Portfolio Help',
            'slug' => 'portfolio-help',
            'content' => 'Portfolio content.',
            'is_published' => 1,
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $accountCategory->id,
            'title' => 'Account Help',
            'slug' => 'account-help',
            'content' => 'Account content.',
            'is_published' => 1,
        ]);

        $response = $this->getJson('/api/help-articles?category=portfolios');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'articles.data')
            ->assertJsonPath('articles.data.0.title', 'Portfolio Help')
            ->assertJsonPath('articles.data.0.category_slug', 'portfolios');
    }

    public function test_it_searches_help_articles(): void
    {
        $dividendCategory = HelpArticleCategory::factory()->create([
            'category_name' => 'Dividends',
            'slug' => 'dividends',
            'is_active' => 1,
        ]);

        $supportCategory = HelpArticleCategory::factory()->create([
            'category_name' => 'Support',
            'slug' => 'support',
            'is_active' => 1,
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $dividendCategory->id,
            'title' => 'How Dividend Tracking Works',
            'slug' => 'how-dividend-tracking-works',
            'summary' => 'Learn about dividend tracking.',
            'content' => 'Dividend distributions and payment dates.',
            'is_published' => 1,
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $supportCategory->id,
            'title' => 'Creating A Support Ticket',
            'slug' => 'creating-a-support-ticket',
            'summary' => 'Contact support.',
            'content' => 'Support ticket content.',
            'is_published' => 1,
        ]);

        $response = $this->getJson('/api/help-articles?search=Dividend');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'articles.data')
            ->assertJsonPath('articles.data.0.title', 'How Dividend Tracking Works');
    }

    public function test_it_shows_a_published_help_article_by_slug(): void
    {
        $category = HelpArticleCategory::factory()->create([
            'category_name' => 'ETF Data',
            'slug' => 'etf-data',
            'is_active' => 1,
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $category->id,
            'title' => 'Understanding ETF Metrics',
            'slug' => 'understanding-etf-metrics',
            'summary' => 'Learn ETF metrics.',
            'content' => '<p>ETF metrics content.</p>',
            'is_published' => 1,
        ]);

        $response = $this->getJson('/api/help-article/understanding-etf-metrics');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('article.title', 'Understanding ETF Metrics')
            ->assertJsonPath('article.slug', 'understanding-etf-metrics')
            ->assertJsonPath('article.category_name', 'ETF Data')
            ->assertJsonPath('article.category_slug', 'etf-data')
            ->assertJsonPath('article.content', '<p>ETF metrics content.</p>');
    }

    public function test_it_does_not_show_unpublished_help_article_by_slug(): void
    {
        $category = HelpArticleCategory::factory()->create([
            'category_name' => 'Support',
            'slug' => 'support',
            'is_active' => 1,
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $category->id,
            'title' => 'Draft Support Article',
            'slug' => 'draft-support-article',
            'content' => 'Draft content.',
            'is_published' => 0,
        ]);

        $response = $this->getJson('/api/help-article/draft-support-article');

        $response->assertNotFound()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Help article not found.');
    }

    public function test_search_must_not_exceed_max_length(): void
    {
        $response = $this->getJson('/api/help-articles?search='.str_repeat('a', 256));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['search']);
    }

    public function test_category_must_not_exceed_max_length(): void
    {
        $response = $this->getJson('/api/help-articles?category='.str_repeat('a', 121));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }
}
