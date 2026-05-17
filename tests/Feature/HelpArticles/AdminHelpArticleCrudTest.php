<?php

namespace Tests\Feature\HelpArticles;

use App\Models\HelpArticle;
use App\Models\HelpArticleCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminHelpArticleCrudTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('personal_access_tokens')->truncate();
        DB::table('help_articles')->truncate();
        DB::table('help_article_categories')->truncate();
        DB::table('users')->truncate();

        $this->admin = User::factory()->create([
            'is_admin' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        DB::table('personal_access_tokens')->truncate();
        DB::table('help_articles')->truncate();
        DB::table('help_article_categories')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    protected function actingAsAdmin(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
    }

    public function test_admin_can_list_help_articles(): void
    {
        $this->actingAsAdmin();

        $category = HelpArticleCategory::factory()->create([
            'category_name' => 'Portfolios',
            'slug' => 'portfolios',
            'is_active' => 1,
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $category->id,
            'title' => 'Portfolio Help',
            'slug' => 'portfolio-help',
            'is_published' => 1,
        ]);

        $response = $this->getJson('/api/manage-help-articles');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'articles.data')
            ->assertJsonPath('articles.data.0.title', 'Portfolio Help')
            ->assertJsonPath('articles.data.0.category_name', 'Portfolios');
    }

    public function test_admin_can_filter_help_articles_by_category(): void
    {
        $this->actingAsAdmin();

        $portfolioCategory = HelpArticleCategory::factory()->create([
            'category_name' => 'Portfolios',
            'slug' => 'portfolios',
        ]);

        $supportCategory = HelpArticleCategory::factory()->create([
            'category_name' => 'Support',
            'slug' => 'support',
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $portfolioCategory->id,
            'title' => 'Portfolio Help',
            'slug' => 'portfolio-help',
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $supportCategory->id,
            'title' => 'Support Help',
            'slug' => 'support-help',
        ]);

        $response = $this->getJson(
            '/api/manage-help-articles?category=portfolios'
        );

        $response->assertOk()
            ->assertJsonCount(1, 'articles.data')
            ->assertJsonPath('articles.data.0.title', 'Portfolio Help');
    }

    public function test_admin_can_search_help_articles(): void
    {
        $this->actingAsAdmin();

        $category = HelpArticleCategory::factory()->create([
            'category_name' => 'ETF Data',
            'slug' => 'etf-data',
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $category->id,
            'title' => 'Understanding ETF Metrics',
            'slug' => 'understanding-etf-metrics',
            'summary' => 'ETF metrics summary',
            'content' => 'ETF metrics content',
        ]);

        HelpArticle::factory()->create([
            'help_article_category_id' => $category->id,
            'title' => 'Dividend Tracking',
            'slug' => 'dividend-tracking',
            'summary' => 'Dividend summary',
            'content' => 'Dividend content',
        ]);

        $response = $this->getJson(
            '/api/manage-help-articles?search=Metrics'
        );

        $response->assertOk()
            ->assertJsonCount(1, 'articles.data')
            ->assertJsonPath(
                'articles.data.0.title',
                'Understanding ETF Metrics'
            );
    }

    public function test_admin_can_view_help_article(): void
    {
        $this->actingAsAdmin();

        $category = HelpArticleCategory::factory()->create([
            'category_name' => 'Accounts',
            'slug' => 'accounts',
        ]);

        $article = HelpArticle::factory()->create([
            'help_article_category_id' => $category->id,
            'title' => 'Account Help',
            'slug' => 'account-help',
            'summary' => 'Account summary',
            'content' => '<p>Account content.</p>',
            'is_published' => 1,
        ]);

        $response = $this->getJson(
            "/api/manage-help-article/{$article->id}"
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('article.id', $article->id)
            ->assertJsonPath('article.title', 'Account Help')
            ->assertJsonPath('article.category_name', 'Accounts');
    }

    public function test_admin_can_create_help_article(): void
    {
        $this->actingAsAdmin();

        $category = HelpArticleCategory::factory()->create([
            'category_name' => 'Support',
            'slug' => 'support',
        ]);

        $response = $this->postJson('/api/create-help-article', [
            'help_article_category_id' => $category->id,
            'title' => 'Creating A Support Ticket',
            'summary' => 'Support summary',
            'content' => '<p>Support content.</p>',
            'is_published' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath(
                'message',
                'Help article created successfully.'
            );

        $this->assertDatabaseHas('help_articles', [
            'title' => 'Creating A Support Ticket',
            'slug' => 'creating-a-support-ticket',
            'help_article_category_id' => $category->id,
        ]);
    }

    public function test_admin_can_update_help_article(): void
    {
        $this->actingAsAdmin();

        $category = HelpArticleCategory::factory()->create([
            'category_name' => 'Dividends',
            'slug' => 'dividends',
        ]);

        $article = HelpArticle::factory()->create([
            'help_article_category_id' => $category->id,
            'title' => 'Old Article',
            'slug' => 'old-article',
            'summary' => 'Old summary',
            'content' => '<p>Old content.</p>',
            'is_published' => 0,
        ]);

        $response = $this->postJson(
            "/api/update-help-article/{$article->id}",
            [
                'help_article_category_id' => $category->id,
                'title' => 'Updated Article',
                'slug' => 'updated-article',
                'summary' => 'Updated summary',
                'content' => '<p>Updated content.</p>',
                'is_published' => 1,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath(
                'message',
                'Help article updated successfully.'
            );

        $this->assertDatabaseHas('help_articles', [
            'id' => $article->id,
            'title' => 'Updated Article',
            'slug' => 'updated-article',
            'is_published' => 1,
        ]);
    }

    public function test_admin_can_delete_help_article(): void
    {
        $this->actingAsAdmin();

        $category = HelpArticleCategory::factory()->create([
            'category_name' => 'Troubleshooting',
            'slug' => 'troubleshooting',
        ]);

        $article = HelpArticle::factory()->create([
            'help_article_category_id' => $category->id,
            'title' => 'Delete Me',
            'slug' => 'delete-me',
        ]);

        $response = $this->deleteJson(
            "/api/delete-help-article/{$article->id}"
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath(
                'message',
                'Help article deleted successfully.'
            );

        $this->assertDatabaseMissing('help_articles', [
            'id' => $article->id,
        ]);
    }

    public function test_guest_cannot_access_admin_help_article_routes(): void
    {
        $response = $this->getJson('/api/manage-help-articles');

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_admin_help_article_routes(): void
    {
        $user = User::factory()->create([
            'is_admin' => 0,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/manage-help-articles');

        $response->assertStatus(401);
    }
}
