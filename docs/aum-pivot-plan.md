# AUM Pivot Plan

Status: **planning — no implementation started** (2026-11-08)
Repos: `etfrocket-api` (this repo) and `etfrocket-web` (`../etfrocket-web`)

## Why

ETF Rocket was built as a broad analysis platform (portfolios, dividends, income projection, backtesting, AI signals). That depends on paid data (Twelve Data for prices/dividends, OpenAI for signals) and there is no funding for it. The issuer-website scrapers that collect AUM/NAV cost nothing beyond hosting. The app is being narrowed to a free, public **ETF AUM tracker**.

## Decisions

| Topic | Decision |
|---|---|
| Product focus | AUM tracking only. Data is public, no login needed to browse. |
| User accounts | Keep existing auth/user code. Signup becomes optional, for "advanced features" (first: a personal watchlist, built later). |
| Frozen data | Price, dividend, portfolio, metrics and AI tables stay in the DB untouched. Not displayed. Not dropped. |
| Coverage | Only the 8 issuers that already have scrapers (YieldMax, Roundhill, Rex, Global X, Neos, TappAlpha, NicholasX, Kurv). Expansion maybe later. |
| Collection frequency | **Daily** (currently weekly per security). |
| Support tickets | Keep for logged-in users. Not exposed publicly. |
| Help center | Keep (cheap). |
| Landing page | **Keep the rocket video intro** (hero section, `LandingPage.jsx` top through the video/title sequence). Rewrite only the feature copy below it (currently advertises AI signals, backtesting, portfolio telemetry). |
| Local data | Never pull from production. Build an artisan command that generates synthetic AUM/NAV history locally. |
| Deploys | Laravel Cloud deploys from `main` (to be confirmed). All pivot work happens on an `aum-pivot` branch in both repos and only reaches `main` when deliberately merged. |

## Ground rules

1. **No destructive migrations.** No migration may drop or alter an existing table during the pivot. New tables (e.g. watchlist) are additive only.
2. **No data changes on production from code paths.** Behavior changes are made in code (e.g. the handler ignores dividend schedules) rather than by updating/deleting live rows.
3. Remove code (routes, controllers, services, queries, jobs, pages, tests), not tables. Models for frozen tables may stay.
4. Tests must pass after each removal step (`./vendor/bin/sail artisan test`).
5. Local dev uses the Sail containers for this repo (`./vendor/bin/sail ...`). Note: the shell `php` alias points at a different project's Sail.

## Current state (findings)

### The AUM pipeline (keep)

```
securities:run-scheduled-updates  (hourly cron, routes/console.php)
  → CronService::runAndLogCron → RunScheduledUpdatesHandler
    → active SecurityUpdateSchedule rows matching run_day + run_hour
    → SecurityIngestionBatch + SecurityIngestionBatchItem
    → RunScheduledSecurityUpdateJob (queued, one per schedule)
        ├─ SecurityUpdateType::FUND_DATA → AiSecurityFundDataExtractionService
        │     → issuer scraper (app/Services/Scrapers/*, config/scrapers.php)   FREE
        │     → AiDataExtraction row (data_source = WEB_SCRAPER)
        │     → ProcessAiSecurityFundDataExtractionService
        │     → security_aum_histories + security_nav_histories (updateOrCreate on security_id+date)
        └─ SecurityUpdateType::DIVIDEND → AiSecurityDividendExtractionService → Twelve Data   PAID
    → FinalizeScheduledUpdatesBatchJob
```

- Despite the "Ai" names, the fund-data path does **not** call OpenAI.
- Scrapers return AUM, NAV, shares outstanding and dates, but only AUM and NAV are persisted. Shares outstanding can be derived (AUM / NAV), so **estimated flows** (Δshares × NAV) need no schema change.
- `securities:calculate-metrics` (daily 02:00, free) already computes start/end AUM, AUM change and % change per `PerformanceRangeType` (5D, 30D, 90D, YTD, 1Y, MAX) into `security_metrics`. Usable for a leaderboard.
- `SymbolAumHistoryChartQuery` already shapes AUM history for charts.

### Existing bug: one-seventh of securities are never updated

`security_update_schedules.run_day` is stored as **1–7** (`SecurityUpdateSchedulesSeederController`: `($slot % 7) + 1`; admin validation allows `0..7`), but `RunScheduledUpdatesHandler` compares it with Carbon's `now()->dayOfWeek`, which is **0–6** (Sunday = 0). Schedules with `run_day = 7` never match. Locally that is 26 of 182 securities for each update type. Moving fund data to daily (Phase 1) sidesteps this for AUM. The `run_day` semantics should still be fixed or documented if day-based schedules remain.

### Paid integrations (stop)

- `securities:run-ai-price-extraction` → Twelve Data quotes (daily 21:00).
- Dividend schedules → Twelve Data dividends (via the shared job above).
- `ai:generate-signals` → OpenAI (hourly).

### Local DB

`mysql` container, database `dailyetfstats` (name inherited from the earlier `/var/www/dailyetfstats` project). Has 182 securities, 364 schedules (182 dividend + 182 fund data, spread over run_day 1–7), 1,092 metrics rows, **0 AUM rows, 0 NAV rows**, 2 users. An older `SecurityAumHistorySeeder` exists, but it only holds hand-entered start/end values for a few symbols.

## Phases

### Phase 0 — Prepare
- [ ] Confirm Laravel Cloud deploy branch (`main`) and that a recent DB backup exists.
- [ ] Create `aum-pivot` branch in both repos from `dev`.
- [ ] **Seed command** (see spec below). Run it locally, then `securities:calculate-metrics`.

### Phase 1 — Stop costs + daily AUM (small; may ship ahead of the rest)
- [ ] Remove `securities:run-ai-price-extraction` and `ai:generate-signals` from `routes/console.php`.
- [ ] `RunScheduledUpdatesHandler`: process only `FUND_DATA` schedules. Dividend schedules remain in the DB, ignored.
- [ ] Daily fund data: for `FUND_DATA`, match on `run_hour` only (ignore `run_day`), every day. Re-runs are idempotent (`updateOrCreate` on `security_id + date`), so weekend runs just refresh Friday's values. That gives 182 scrapes/day spread across the hours.
- [ ] Update/add tests for the handler.
- Possible fast-track: this phase could go to `main` on its own to stop spend immediately. Decide when we get there.

### Phase 2 — Backend pruning (`aum-pivot`)
Remove routes + controllers + services + queries + jobs + commands + tests for:
- Portfolios, portfolio transactions (incl. CSV import/export, `config/import_transaction_aliases.php`), holdings, portfolio signals
- Dividends, income projection
- Backtesting
- Comparisons / radar / metric explorer (keep `SymbolAumHistoryChartQuery` or move it)
- AI signals, market snapshot/conditions/events, market analytics, mission control
- Twelve Data + OpenAI services; price/dividend extraction jobs, commands and handlers; `BackfillPriceHistory` + `app/Imports/PriceData`

Keep: auth, users/settings, admin (users, securities, issuers, selects, help articles), support tickets (authenticated only), monitoring (cron logs, import logs), scrapers, fund-data pipeline, AUM portion of metrics, cleanup/trim crons, health check, Cashier `Billable` (unused but harmless).

Order: one domain per commit, tests green after each.

### Phase 3 — Public AUM API
Unauthenticated, cached, rate-limited endpoints (names follow the existing kebab-case RPC style; final names TBD):
- Leaderboard: all tracked ETFs with latest AUM, NAV, change over 5D / 30D / 90D / YTD / 1Y, sortable, filterable by issuer/strategy.
- ETF detail: AUM + NAV history, derived shares outstanding, estimated daily flows.
- Issuer summary: total AUM per issuer and change.
- Possibly "biggest movers" (inflows/outflows).

### Phase 4 — Frontend (`etfrocket-web`, `aum-pivot`)
- Landing page: keep video intro; rewrite feature copy for AUM tracking; link to the public leaderboard.
- New public pages under `PublicLayout`: leaderboard, ETF detail, issuer view.
- Remove pages + `src/api/*` modules for pruned features. Shrink user dashboard to settings + support (watchlist later).
- Admin area unchanged except removing pages for deleted backends (e.g. data management actions for price backfill).

### Phase 5 — Watchlist (later)
- New additive tables (e.g. `watchlist_items`: user_id, security_id).
- Logged-in users star ETFs and get a personal leaderboard view.

### Phase 6 — Not planned
Dropping frozen tables. Revisit only if the data is confirmed unwanted.

## Seed command spec (Phase 0)

`php artisan dev:seed-fund-data {--days=365} {--symbol=} {--fresh}`

- **Refuses to run in production** (`app()->environment('production')` → error, exit non-zero).
- For each active security with a `SecurityDetail` (or `--symbol`), generates a synthetic daily series for the last `--days` days, excluding weekends:
  - NAV: random walk with small drift and volatility. Income ETFs (most of this universe) get a slow NAV decay and periodic distribution drops.
  - Shares outstanding: creation/redemption steps with a per-fund trend (some growing, some shrinking, some flat) so leaderboards and flows look realistic.
  - AUM = NAV × shares (bigint).
- Deterministic per symbol (seeded from the symbol) so runs are reproducible.
- Writes `security_aum_histories` and `security_nav_histories` in chunked upserts on the existing unique keys. `data_source_id = DataSource::MANUAL_ENTRY` so synthetic rows are distinguishable from scraped ones.
- `--fresh` truncates those two tables first (local only, still behind the production guard).
- Prints a summary and suggests running `securities:calculate-metrics` afterward.
- Feature test: generates rows for a seeded security; refuses under `APP_ENV=production`.

## Open items
- Confirm Laravel Cloud deploy branch and backup status.
- Whether Phase 1 ships to production ahead of the rest.
- Final public endpoint names and what the leaderboard's default sort/columns are.
- Whether to fix `run_day` 1–7 vs 0–6 for any day-based schedules that remain.
