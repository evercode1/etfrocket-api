<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Models\HelpArticleCategory;
use Illuminate\Support\Str;

class HelpArticlesSeederController extends Controller
{

    public function run(): void
    {
        if (! env('ALLOW_SEEDS')) {
            return;
        }

        HelpArticle::truncate();

        $articles = [

            /*
            |--------------------------------------------------------------------------
            | Getting Started
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Getting Started',
                'title' => 'Welcome To ETF Rocket',
                'summary' => 'Learn what ETF Rocket is and how to begin exploring ETF analytics.',
                'content' => '
                    <p>ETF Rocket helps investors analyze ETFs with a focus on yield sustainability, historical performance, and portfolio insights.</p>

                    <p>You can compare ETFs, build portfolios, and explore detailed analytics throughout the platform.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | Accounts
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Accounts',
                'title' => 'How To Change Your Email Address',
                'summary' => 'Update your account email from the settings dashboard.',
                'content' => '
                    <p>Navigate to Dashboard → Settings → Update Email.</p>

                    <p>Enter your new email address and confirm it to save changes.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | Portfolios
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Portfolios',
                'title' => 'Creating Your First Portfolio',
                'summary' => 'Learn how to create and organize ETF portfolios.',
                'content' => '
                    <p>Go to Dashboard → Portfolios → Create Portfolio.</p>

                    <p>You can organize holdings, track performance, and compare allocation strategies.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | ETF Data
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'ETF Data',
                'title' => 'Understanding ETF Metrics',
                'summary' => 'Learn how ETF Rocket calculates and displays ETF metrics.',
                'content' => '
                    <p>ETF Rocket aggregates historical pricing, dividends, NAV data, and fund metadata to calculate metrics.</p>

                    <p>Metrics are designed to help users evaluate sustainability and long-term behavior.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | Dividends
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Dividends',
                'title' => 'How Dividend Tracking Works',
                'summary' => 'Learn how ETF Rocket tracks distributions and yield data.',
                'content' => '
                    <p>Dividend data includes ex-dividend dates, payment dates, and distribution amounts.</p>

                    <p>Yield calculations are based on imported historical distribution records.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | Imports
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Imports',
                'title' => 'Importing Portfolio Transactions',
                'summary' => 'Upload transaction history into ETF Rocket portfolios.',
                'content' => '
                    <p>ETF Rocket supports importing portfolio transaction data from supported CSV formats.</p>

                    <p>Navigate to your portfolio and choose Import Transactions to begin.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | Support
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Support',
                'title' => 'Creating A Support Ticket',
                'summary' => 'Contact ETF Rocket support through the integrated ticket system.',
                'content' => '
                    <p>Open Dashboard → Support → New Ticket.</p>

                    <p>Select the appropriate topic and describe your issue in detail.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | Billing
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Billing',
                'title' => 'Does ETF Rocket Require A Subscription?',
                'summary' => 'Learn about ETF Rocket pricing and future plans.',
                'content' => '
                    <p>ETF Rocket currently provides core features free of charge.</p>

                    <p>Additional premium analytics may be introduced in the future.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | Troubleshooting
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Troubleshooting',
                'title' => 'Why Is My ETF Data Missing?',
                'summary' => 'Common reasons ETF information may not appear correctly.',
                'content' => '
                    <p>Some ETFs may have incomplete historical records or pending import updates.</p>

                    <p>If data appears incorrect, please contact support through the dashboard.</p>
                ',
            ],

        ];

        foreach ($articles as $article) {

            $category = HelpArticleCategory::where(
                'category_name',
                $article['category']
            )->first();

            if (! $category) {
                continue;
            }

            HelpArticle::updateOrCreate(
                [
                    'slug' => Str::slug($article['title']),
                ],
                [
                    'help_article_category_id' => $category->id,
                    'title' => $article['title'],
                    'summary' => $article['summary'],
                    'content' => trim($article['content']),
                    'is_published' => 1,
                ]
            );
        }
    }
}
