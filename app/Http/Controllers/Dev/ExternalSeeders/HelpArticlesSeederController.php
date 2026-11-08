<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Models\HelpArticleCategory;
use Illuminate\Support\Str;

class HelpArticlesSeederController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Help center articles
    |--------------------------------------------------------------------------
    |
    | Content is HTML rendered inside the help article page's prose styles.
    | Each 'category' must exist in HelpArticleCategoriesSeederController.
    | Run the categories seeder first.
    |
    */

    public function run(): void
    {
        if (! env('ALLOW_SEEDS')) {
            return;
        }

        HelpArticle::truncate();

        foreach ($this->articles() as $article) {

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

    public function articles(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Getting Started
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Getting Started',
                'title' => 'Welcome to ETF Rocket',
                'summary' => 'What ETF Rocket tracks and how to start exploring.',
                'content' => '
                    <p>ETF Rocket tracks <strong>assets under management (AUM)</strong> for income-focused ETFs every day, so you can see which funds are attracting money and which are losing it.</p>

                    <p>For every ETF we cover you can see:</p>

                    <ul>
                        <li>Its current AUM and net asset value (NAV) per share</li>
                        <li>How its AUM has changed over the past week, month, quarter, year to date and year</li>
                        <li>Estimated net fund flows, meaning how much new money has moved in or out</li>
                        <li>Charts of its full history since we started tracking it</li>
                    </ul>

                    <h2>Where to start</h2>

                    <ul>
                        <li><a href="/aum">The AUM Leaderboard</a> ranks every covered ETF by size and lets you sort by growth or flows.</li>
                        <li>The search bar at the top of every page takes you straight to any ETF.</li>
                        <li>The home page highlights this week\'s biggest inflows and outflows.</li>
                    </ul>

                    <p>ETF Rocket is free to use and you don\'t need an account.</p>
                ',
            ],

            [
                'category' => 'Getting Started',
                'title' => 'Finding an ETF',
                'summary' => 'Use the search bar to jump to any ETF by symbol, name or issuer.',
                'content' => '
                    <p>The search bar at the top of every page searches every ETF we track. You can type:</p>

                    <ul>
                        <li>A <strong>ticker symbol</strong>, such as <code>ULTY</code></li>
                        <li>Part of the <strong>fund name</strong>, such as <code>bitcoin</code> or <code>nasdaq</code></li>
                        <li>An <strong>issuer</strong>, such as <code>Roundhill</code></li>
                    </ul>

                    <p>Use the arrow keys to move through the results and press <strong>Enter</strong> to open one, or click a result. On a computer you can press the <strong>/</strong> key from anywhere on the page to jump to the search bar.</p>

                    <p>If the search says we don\'t currently provide coverage for a symbol, that ETF isn\'t tracked yet. See <a href="/help/which-etfs-does-etf-rocket-cover">Which ETFs does ETF Rocket cover?</a></p>
                ',
            ],

            [
                'category' => 'Getting Started',
                'title' => 'Using the Home Page',
                'summary' => 'What the live stats and "This week\'s movers" on the home page show.',
                'content' => '
                    <p>Below the headline on the home page you\'ll find a quick snapshot of the data:</p>

                    <ul>
                        <li><strong>Tracked</strong>: the combined AUM of every ETF we cover</li>
                        <li><strong>ETFs</strong> and <strong>Issuers</strong>: how many funds and fund companies we track</li>
                        <li><strong>Updated</strong>: the most recent date we have data for</li>
                    </ul>

                    <h2>This week\'s movers</h2>

                    <p>The movers cards list the three ETFs with the largest estimated <strong>inflows</strong> and the three with the largest estimated <strong>outflows</strong> over the past week. Click any fund to open its page, or choose <strong>See all</strong> to open the leaderboard sorted by one-week flows.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | AUM Leaderboard
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'AUM Leaderboard',
                'title' => 'Reading the AUM Leaderboard',
                'summary' => 'What each column on the leaderboard means.',
                'content' => '
                    <p>The <a href="/aum">AUM Leaderboard</a> lists every covered ETF, ranked by its current assets under management.</p>

                    <h2>Summary cards</h2>

                    <p>The cards at the top show the total AUM, the total change over the selected period, and the total estimated net flows. They update to match any issuer filter or search you apply.</p>

                    <h2>Columns</h2>

                    <ul>
                        <li><strong>#</strong>: the ETF\'s rank by AUM</li>
                        <li><strong>ETF</strong>: the ticker symbol and fund name</li>
                        <li><strong>Issuer</strong>: the fund company that manages the ETF</li>
                        <li><strong>AUM</strong>: the most recent assets under management</li>
                        <li><strong>Change</strong>: how much AUM has changed over the selected period, in dollars</li>
                        <li><strong>%</strong>: the same change as a percentage</li>
                        <li><strong>Est. Flows</strong>: estimated new money in or out over the period. See <a href="/help/how-estimated-fund-flows-are-calculated">How estimated fund flows are calculated</a>.</li>
                        <li><strong>NAV</strong>: the most recent net asset value per share</li>
                    </ul>

                    <h2>Time periods</h2>

                    <p>Use the <strong>1W, 1M, 3M, YTD and 1Y</strong> buttons to change the period used for the change and flow columns.</p>

                    <p>Click any row to open that ETF\'s page with full charts. On smaller screens some columns are hidden to save space.</p>
                ',
            ],

            [
                'category' => 'AUM Leaderboard',
                'title' => 'Sorting, Filtering and Sharing the Leaderboard',
                'summary' => 'Sort by any column, filter by issuer, and share a link to your view.',
                'content' => '
                    <h2>Sorting</h2>

                    <p>Click any column heading to sort by it. Click the same heading again to reverse the order. The active column is highlighted with an arrow showing the direction.</p>

                    <p>Number columns sort largest first, and text columns sort A to Z. ETFs without a value for the selected period, such as funds that launched recently, always appear at the bottom.</p>

                    <p>On a phone, use the <strong>Sort</strong> menu and the direction button above the table.</p>

                    <h2>Filtering</h2>

                    <ul>
                        <li>Use the <strong>All issuers</strong> menu to show one fund company\'s ETFs.</li>
                        <li>Use the search box above the table to filter by symbol or name.</li>
                    </ul>

                    <h2>Sharing</h2>

                    <p>Your selected period, issuer filter and sort are saved in the page address. Copy the link from your browser to share exactly what you\'re looking at, for example the biggest one-month inflows from a single issuer.</p>
                ',
            ],

            [
                'category' => 'AUM Leaderboard',
                'title' => 'What "New" and "Stale" Mean',
                'summary' => 'Labels that explain missing changes and out-of-date data.',
                'content' => '
                    <h2>New</h2>

                    <p>An ETF marked <strong>New</strong> in the percentage column started trading, or we started tracking it, during the selected period. There is no starting value to compare against, so its change and flows for that period are left blank. Choose a shorter period to see its changes.</p>

                    <h2>Stale</h2>

                    <p>An ETF marked <strong>Stale</strong> hasn\'t had new data for more than seven days. Its numbers are shown as of the date beside the label and may be out of date. See <a href="/help/why-is-an-etf-marked-out-of-date">Why is an ETF marked out of date?</a></p>

                    <h2>"As of" dates</h2>

                    <p>If an ETF\'s latest data is from an earlier date than the rest of the leaderboard, that date appears under its AUM.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | ETF Pages & Charts
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'ETF Pages & Charts',
                'title' => 'Reading an ETF Page',
                'summary' => 'A tour of the summary, charts and data table on each ETF page.',
                'content' => '
                    <p>Each ETF page shows the fund\'s issuer, strategy, the date we started tracking it, when its data was last updated, and a link to the fund\'s page on the issuer\'s website.</p>

                    <h2>Summary cards</h2>

                    <ul>
                        <li><strong>AUM</strong>: the latest assets under management and its date</li>
                        <li><strong>Change</strong>: how AUM changed over the selected time range</li>
                        <li><strong>Est. net flows</strong>: estimated new money in or out over the range</li>
                        <li><strong>NAV</strong>: the latest net asset value per share and its change over the range</li>
                    </ul>

                    <h2>Charts</h2>

                    <p>The main chart shows AUM, shares outstanding or NAV over time. The flows chart below it shows estimated money moving in and out. Hover over or tap either chart to see exact values for a date.</p>

                    <h2>Data table</h2>

                    <p>Choose <strong>Show data table</strong> to see every daily value in the selected range, newest first.</p>
                ',
            ],

            [
                'category' => 'ETF Pages & Charts',
                'title' => 'Choosing a Time Range',
                'summary' => 'How the 1M to Max buttons change the charts and summary cards.',
                'content' => '
                    <p>The <strong>1M, 3M, 6M, YTD, 1Y and Max</strong> buttons control the period shown in the charts, the summary cards and the data table.</p>

                    <ul>
                        <li><strong>YTD</strong> starts from the last value of the previous year.</li>
                        <li><strong>Max</strong> shows everything since we started tracking the ETF.</li>
                    </ul>

                    <p>If the ETF launched, or we started tracking it, after the start of the range you choose, the change is measured <strong>since launch</strong> and labeled that way.</p>
                ',
            ],

            [
                'category' => 'ETF Pages & Charts',
                'title' => 'AUM, Shares and NAV Charts',
                'summary' => 'Switch the main chart between assets, shares outstanding and NAV.',
                'content' => '
                    <p>Use the <strong>AUM</strong>, <strong>Shares</strong> and <strong>NAV</strong> buttons above the main chart to switch what it shows.</p>

                    <ul>
                        <li><strong>AUM</strong> is the total value of the fund. It rises and falls with both investor money and investment performance.</li>
                        <li><strong>Shares</strong> is the number of fund shares outstanding. It changes only when shares are created or redeemed, so it\'s the clearest picture of money moving in or out.</li>
                        <li><strong>NAV</strong> is the value of one share. It reflects investment performance and distributions paid to shareholders.</li>
                    </ul>

                    <p>Comparing the charts helps explain AUM changes. For example, if AUM fell while shares outstanding rose, investors were adding money but the fund\'s value per share declined.</p>
                ',
            ],

            [
                'category' => 'ETF Pages & Charts',
                'title' => 'The Estimated Net Flows Chart',
                'summary' => 'How to read inflow and outflow bars.',
                'content' => '
                    <p>The flows chart shows estimated money moving into or out of the ETF.</p>

                    <ul>
                        <li>Bars <strong>above zero</strong> are inflows: new shares were created.</li>
                        <li>Bars <strong>below zero</strong> are outflows: shares were redeemed.</li>
                    </ul>

                    <p>For ranges of three months or less each bar is one day. For longer ranges, bars are grouped by week so the chart stays readable.</p>

                    <p>Days with no bar had no change in shares outstanding. See <a href="/help/how-estimated-fund-flows-are-calculated">How estimated fund flows are calculated</a> for details.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | Data & Methodology
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Data & Methodology',
                'title' => 'What Is AUM?',
                'summary' => 'Assets under management, and why it matters.',
                'content' => '
                    <p><strong>Assets under management (AUM)</strong> is the total market value of everything an ETF holds. It equals the number of shares outstanding multiplied by the net asset value (NAV) per share.</p>

                    <p>AUM changes for two reasons:</p>

                    <ul>
                        <li><strong>Fund flows</strong>: investors buy or sell enough of the ETF that shares are created or redeemed.</li>
                        <li><strong>Performance and distributions</strong>: the value of the fund\'s holdings changes, and income paid out to shareholders lowers the NAV.</li>
                    </ul>

                    <p>Tracking AUM over time shows whether a fund is growing or shrinking. Estimated flows separate how much of that change came from investors rather than performance.</p>
                ',
            ],

            [
                'category' => 'Data & Methodology',
                'title' => 'How Estimated Fund Flows Are Calculated',
                'summary' => 'Flows are estimated from changes in shares outstanding.',
                'content' => '
                    <p>We estimate fund flows from the change in the number of shares outstanding:</p>

                    <ol>
                        <li><strong>Shares outstanding</strong> for each day is calculated as AUM divided by NAV.</li>
                        <li><strong>Estimated flow</strong> for a period is the change in shares outstanding multiplied by the latest NAV.</li>
                    </ol>

                    <p>Because flows are based on shares, investment gains, losses and distributions do <strong>not</strong> count as flows. A fund whose NAV falls after paying a distribution, with no change in shares, shows zero flow.</p>

                    <h2>Why "estimated"?</h2>

                    <p>Issuers don\'t always publish flows directly, and the AUM and NAV figures we use can be rounded or published at slightly different times. Treat flows as a close approximation, not an official figure.</p>
                ',
            ],

            [
                'category' => 'Data & Methodology',
                'title' => 'How Period Changes Are Calculated',
                'summary' => 'Which values are compared for 1W, 1M, 3M, YTD and 1Y.',
                'content' => '
                    <p>Each change compares an ETF\'s latest value with its value at the start of the period.</p>

                    <ul>
                        <li><strong>1W, 1M, 3M and 1Y</strong> start one week, one month, three months or one year before the most recent data date.</li>
                        <li><strong>YTD</strong> starts on December 31 of the previous year.</li>
                    </ul>

                    <p>Markets are closed on weekends and holidays, so the starting value is the most recent one on or before the start date.</p>

                    <p>If we have no value that old, because the ETF launched or we began tracking it during the period, the change is left blank and the ETF is marked <strong>New</strong>.</p>
                ',
            ],

            [
                'category' => 'Data & Methodology',
                'title' => 'Where the Data Comes From',
                'summary' => 'How and when AUM and NAV data is collected.',
                'content' => '
                    <p>We collect AUM and NAV for each covered ETF <strong>every day, directly from the fund issuer\'s website</strong>. Each ETF page links to the fund\'s page on the issuer\'s website.</p>

                    <h2>Timing</h2>

                    <ul>
                        <li>Issuers usually publish figures as of the previous trading day, so our latest date is often one business day behind.</li>
                        <li>Markets are closed on weekends and holidays, so no new data appears for those days.</li>
                        <li>ETFs are collected at different times throughout the day, so one fund may update a few hours before another.</li>
                    </ul>

                    <p>We check each value before saving it, but we can\'t guarantee the accuracy of issuer data. Always confirm important figures with the issuer and the fund\'s prospectus.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | Coverage
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Coverage',
                'title' => 'Which ETFs Does ETF Rocket Cover?',
                'summary' => 'The fund issuers and ETFs we currently track.',
                'content' => '
                    <p>We focus on income-oriented ETFs, including option-income, covered-call and weekly-distribution funds. We currently collect data from these issuers:</p>

                    <ul>
                        <li>YieldMax</li>
                        <li>Roundhill Investments</li>
                        <li>REX Shares</li>
                        <li>Global X</li>
                        <li>NEOS Investments</li>
                        <li>Kurv Investment Management</li>
                        <li>NicholasX</li>
                        <li>TappAlpha</li>
                    </ul>

                    <p>The <a href="/aum">AUM Leaderboard</a> always shows the full, current list of covered ETFs. You can filter it by issuer to see every fund from one company.</p>
                ',
            ],

            [
                'category' => 'Coverage',
                'title' => 'What "Data Coming Soon" Means',
                'summary' => 'Covered ETFs that are waiting for their first data.',
                'content' => '
                    <p>An ETF marked <strong>Data coming soon</strong> is one we track, but we haven\'t collected its first data yet. This usually happens when a fund has just been added.</p>

                    <ul>
                        <li>Its page shows the fund name and issuer, along with a note that data isn\'t available yet.</li>
                        <li>It doesn\'t appear in the leaderboard rankings until data arrives. The leaderboard shows how many ETFs are waiting.</li>
                    </ul>

                    <p>Data is collected daily, so these funds usually appear within a day or two.</p>
                ',
            ],

            [
                'category' => 'Coverage',
                'title' => 'Why Isn\'t an ETF Covered?',
                'summary' => 'Why some ETFs aren\'t tracked, and what you\'ll see if you search for one.',
                'content' => '
                    <p>If you search for an ETF we don\'t track, you\'ll see <strong>"We don\'t currently provide coverage"</strong> for that symbol.</p>

                    <p>We only cover ETFs whose issuers publish AUM and NAV on their websites in a form we can collect reliably every day. ETFs from other issuers, and most broad-market funds, aren\'t covered at this time.</p>

                    <p>We plan to add more issuers over time. New coverage appears automatically in search and on the <a href="/aum">AUM Leaderboard</a>.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | Troubleshooting
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'Troubleshooting',
                'title' => 'Why Is an ETF Marked Out of Date?',
                'summary' => 'What it means when an ETF\'s data is stale.',
                'content' => '
                    <p>We mark an ETF as <strong>stale</strong> when we haven\'t been able to collect new data for it in more than seven days. You\'ll see a <strong>Stale</strong> label on the leaderboard and a notice at the top of its page showing when it was last updated.</p>

                    <p>Common reasons include:</p>

                    <ul>
                        <li>The issuer changed or reorganized its website.</li>
                        <li>The issuer hasn\'t published new figures for the fund.</li>
                        <li>The fund has closed, merged or changed its ticker.</li>
                    </ul>

                    <p>We monitor collection every day and work to restore missing data as quickly as possible. Until then, the last known figures stay visible with their date.</p>
                ',
            ],

            [
                'category' => 'Troubleshooting',
                'title' => 'Why Don\'t the Numbers Match the Issuer\'s Website?',
                'summary' => 'Common reasons for small differences from issuer figures.',
                'content' => '
                    <p>Small differences between ETF Rocket and an issuer\'s website are usually caused by:</p>

                    <ul>
                        <li><strong>Timing</strong>: the issuer may have published newer figures since we last collected them. Check the "as of" date on the ETF page.</li>
                        <li><strong>Rounding</strong>: we show large values in compact form, such as $1.25B, so small differences can come from rounding.</li>
                        <li><strong>Calculated figures</strong>: shares outstanding and flows are calculated from AUM and NAV, so they can differ slightly from figures the issuer publishes directly.</li>
                    </ul>

                    <p>The issuer\'s website and the fund\'s prospectus are the official sources. The <strong>Issuer page</strong> link on each ETF page takes you straight there.</p>
                ',
            ],

            [
                'category' => 'Troubleshooting',
                'title' => 'Why Is a Change Blank or Showing a Dash?',
                'summary' => 'When changes and flows can\'t be calculated.',
                'content' => '
                    <p>A dash (—) or a <strong>New</strong> label appears when there isn\'t enough history to calculate a value:</p>

                    <ul>
                        <li>The ETF launched, or we started tracking it, during the selected period, so there\'s no starting value to compare.</li>
                        <li>NAV wasn\'t available for one of the dates, so shares outstanding and flows couldn\'t be calculated.</li>
                    </ul>

                    <p>Try a shorter time period, or open the ETF\'s page and choose <strong>Max</strong> to see all the history we have.</p>
                ',
            ],

            /*
            |--------------------------------------------------------------------------
            | About ETF Rocket
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'About ETF Rocket',
                'title' => 'Is ETF Rocket Free?',
                'summary' => 'Pricing, and whether you need an account.',
                'content' => '
                    <p>Yes. The AUM Leaderboard, ETF pages, charts and search are all free to use.</p>

                    <p>You don\'t need an account to use any of the data. Optional accounts may be offered in the future for personal features, such as saving a watchlist of ETFs.</p>
                ',
            ],

            [
                'category' => 'About ETF Rocket',
                'title' => 'Is ETF Rocket Investment Advice?',
                'summary' => 'ETF Rocket provides information only, not recommendations.',
                'content' => '
                    <p><strong>No.</strong> ETF Rocket is an informational and educational service. Nothing on the site is investment, financial, legal, tax or trading advice, or a recommendation to buy, sell or hold any security.</p>

                    <ul>
                        <li>Rankings, flows and changes describe what has happened. They don\'t predict what will happen.</li>
                        <li>Growing AUM or strong inflows don\'t mean a fund is a good investment for you.</li>
                        <li>Data is collected automatically and may be delayed or contain errors.</li>
                    </ul>

                    <p>Always read a fund\'s prospectus and consider speaking with a licensed financial professional before investing. See our <a href="/terms-of-service">Terms of Service</a> for full details.</p>
                ',
            ],

            [
                'category' => 'About ETF Rocket',
                'title' => 'Is ETF Rocket Affiliated With Any Fund Issuer?',
                'summary' => 'Our relationship to the ETFs and issuers we cover.',
                'content' => '
                    <p>No. ETF Rocket is independent and is not affiliated with, endorsed by, or sponsored by any ETF issuer or fund shown on the site.</p>

                    <p>Fund names and trademarks belong to their respective owners. Covering an ETF doesn\'t mean we endorse it.</p>
                ',
            ],

        ];
    }
}
