<?php

/*
|--------------------------------------------------------------------------
| Security Pipeline Cleanup Configuration
|--------------------------------------------------------------------------
|
| Controls app:cleanup-ai-pipeline-data (daily), which permanently deletes
| transient pipeline records older than each table's retention period.
| The AUM / NAV history itself lives in other tables and is never touched.
|
| List child tables before their parents to avoid foreign key violations
| when cascading deletes are not configured.
|
| Tables that are NOT listed are never cleaned up. The AI signal tables
| (ai_signal_batch_items, ai_signal_batches, ai_market_signals) were
| removed on purpose: signal generation is disabled and their data is
| kept frozen.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Retention per table (hours)
    |--------------------------------------------------------------------------
    */

    'tables' => [

        // Scrape attempts and batches. The admin Pipeline Health page reports
        // 7 days of scrape history, so keep two weeks.
        'security_ingestion_batch_items' => 24 * 14,

        'security_ingestion_batches' => 24 * 14,

        // One row per scrape with the raw issuer payload; grows quickly.
        'ai_data_extractions' => 72,

    ],

];
