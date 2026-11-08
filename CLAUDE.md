# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

ETF Rocket API: a Laravel 13 (PHP 8.3+) JSON API for an ETF portfolio / income-tracking app, with Sanctum token auth and Laravel Cashier. It serves only JSON; the UI is a separate React SPA at `../etfrocket-web` (`/Users/billk/var/www/etfrocket-web`), which has its own CLAUDE.md. Most features span both repos: a new screen usually needs a route + controller here and an API function in `etfrocket-web/src/api/`.

## Commands

```bash
composer dev                         # artisan serve + queue:listen + pail logs + vite, run concurrently
php artisan test                     # full suite (composer test also clears config cache first)
php artisan test --filter=GetPortfolioSelectsTest     # single test class or method
php artisan test tests/Feature/Portfolios             # one directory
./vendor/bin/pint                    # code style (Laravel preset; no pint.json)
php artisan schedule:list            # view the cron schedule defined in routes/console.php
```

`compose.yaml` defines a Sail stack (MySQL 8.4, Redis, Meilisearch, Mailpit). The frontend dev server runs on `http://localhost:5173`, which is the only non-production origin allowed in `config/cors.php`.

### Tests

- PHPUnit 12, `tests/Unit` and `tests/Feature`, organized by domain.
- Tests hit a real database: `phpunit.xml` sets `DB_DATABASE=testing`, and the queue runs `sync`. Tests do **not** use `RefreshDatabase`. Each test truncates the tables it touches in `setUp()` / `tearDown()` (`DB::table('...')->truncate()`). Follow that pattern, and never point tests at a database with real data.
- Auth in tests: `Sanctum::actingAs($user, ['*'])`. External HTTP (OpenAI, Twelve Data, issuer sites) is stubbed with `Http::fake([...])`.

## Architecture

### Routing

- `routes/api.php` contains almost no routes itself. It pulls in per-domain files with `IncludeRoutes::file('routes/<Area>/<Domain>/<file>.php')`. Areas: `routes/Auth`, `routes/Admin/<Domain>`, `routes/User/<Domain>`. **A new route file does nothing until it is registered in `routes/api.php`.**
- All routes get Laravel's `/api` prefix. Endpoints are RPC-style kebab-case verbs, not REST resources: `/list-portfolios`, `/view-portfolio/{id}`, `/create-portfolio`, `/update-portfolio/{id}`, `/delete-portfolio/{id}`. The frontend calls these exact paths, so renaming one breaks the SPA.
- Middleware groups inside each route file: `auth:sanctum` for users, `auth:sanctum` + `isAdmin` for admin. `allowSeeds` / `allowExternalData` (aliases in `bootstrap/app.php`) gate seed/data-feed endpoints on the `ALLOW_SEEDS` / `ALLOW_EXTERNAL_DATA` env flags.

### Request flow (Controller → Service / Query)

- Controllers live in `app/Http/Controllers/{Admin,User,Public}/<Domain>/` and stay thin. Dependencies are method-injected, and the work is delegated to:
  - `app/Services/<Domain>/` for business logic and writes, typically one class per action (`CreatePortfolioService`, `ViewPortfolioService`, …) using `DB::transaction` for multi-step writes
  - `app/Queries/<Domain>/` for read-heavy and reporting queries
- Standard response envelope: `{ "success": true, "data": ... }` on success. On failure, controllers catch `\Exception`, `Log::error(...)` the context (`user_id`, ids, message), and return `{ "success": false, "message": "Oops, something went wrong..." }` with a 500. The frontend reads `response.data.data`.
- Validation is mostly inline (`Validator::make` / `$request->validate`) in controllers. A few FormRequests exist in `app/Http/Requests`.
- Use `App\Utilities\Auth::id()` / `Auth::user()` (a thin static wrapper around `auth()`) rather than the facade. `Auth::logout()` deletes all of the user's Sanctum tokens.
- **Form-config endpoints:** create/edit screens fetch `get-create-*-form-config` / `get-update-*-form-config/{id}`, which return field definitions and select options. Dropdown options come from lookup models (`Status`, `TransactionType`, `SecurityType`, …) via `App\Utilities\DropDownListMaker`. Admin-editable select lists are declared in `config/admin_selects.php`. Lookup IDs are referenced by model constants (e.g. `Status::ACTIVE`), not magic numbers.

### Data pipeline (scheduled)

The app is being narrowed to a free ETF AUM tracker (see `docs/aum-pivot-plan.md`). `routes/console.php` defines the schedule (all UTC, `withoutOverlapping`):

| Command | When | What |
|---|---|---|
| `securities:run-scheduled-updates` | hourly | Dispatches FUND_DATA schedules at their `run_hour` (every day; `run_day` ignored) for issuers that have a scraper. Scrapes AUM + NAV. DIVIDEND schedules are skipped. |
| `app:cleanup-ai-pipeline-data` | daily 05:15 | Per-table retention from `config/security_pipeline_cleanup.php` (ingestion batches/items 14 days, `ai_data_extractions` 72h). AI signal tables are deliberately excluded (frozen data). |
| `app:trim-cron-logs`, `app:trim-import-logs` | weekly | Keep one week of logs |

No longer scheduled (commands still exist): `ai:generate-signals` (OpenAI), `securities:run-ai-price-extraction` (Twelve Data), `securities:calculate-metrics` (fed only retired features). When changing the schedule, update `PipelineHealthQuery::EXPECTED_CRONS`; a test enforces they match.

- Each command delegates to `CronService::runAndLogCron(...)`, which resolves a handler from `app/Services/Crons/Handlers/<Name>Handler.php` by class name, records a `CronLog`, and sends failure notifications. New crons follow the same Command → Handler pattern.
- Heavy work fans out to queued batch jobs in `app/Jobs` (`Run*Job` per security plus a `Finalize*BatchJob`), so a queue worker must be running for these to complete outside tests.
- `app/Services/AI/`:
  - `Extractions/` uses OpenAI (Responses API, model from `config('services.openai.model')`, default `gpt-4.1-mini`) to extract price, dividend and fund data. Market data comes from Twelve Data (`config('services.twelve_data')`).
  - `AiSignals/` builds payloads and fills the markdown prompt templates in `AiSignals/Templates/*.md`.
- `app/Services/Scrapers/` has one scraper per ETF issuer (YieldMax, Roundhill, Kurv, Neos, GlobalX, RexShares, TappAlpha, NicholasX). Per-issuer settings such as page IDs are in `config/scrapers.php`.
- Historical prices: `securities:backfill-price-history {symbol?}` imports from the per-symbol files in `app/Imports/PriceData/*.txt`.
- TappAlpha history: `securities:backfill-tappalpha-history {--symbol=} {--dry-run}` copies each fund's daily AUM/NAV history from TappAlpha's public fund data API (the same API `TappAlphaScraperService` uses). It only inserts missing dates and never overwrites, so it is safe to run in production and to re-run.

### Domain config

Tunable domain settings live in dedicated config files rather than in code: `security_metrics.php`, `security_filters.php`, `security_comparison.php`, `ai-signals.php`, `imports.php`, `import_transaction_aliases.php` (maps broker CSV transaction labels during portfolio imports), and `security_pipeline_cleanup.php`.

### Code style

Beyond Pint, the codebase uses `/* |---| Section |---| */` banner comments to divide longer methods into steps, and leaves a blank line after opening braces in `try` and closure blocks. Match the surrounding file.
