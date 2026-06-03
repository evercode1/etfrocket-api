<?php

/**
 * AI Signals Configuration
 *
 * This file defines the market telemetry universe used by the
 * AI Signals system.
 *
 * The TwelveDataStatsService will use these symbols to build
 * a normalized market snapshot that is later consumed by:
 *
 * - MarketSnapshotPayloadService
 * - MarketConditionsPayloadService
 * - Future AI signal generators
 *
 * Categories are intentionally separated by purpose:
 *
 * indexes
 *     Broad market benchmarks used to measure overall market direction.
 *
 * sectors
 *     Sector ETFs used to measure sector rotation and leadership.
 *
 * bonds
 *     Treasury ETFs used to measure risk-on vs risk-off behavior.
 *
 * volatility
 *     Fear and volatility indicators.
 *
 * leadership
 *     Specialized market leadership indicators used to identify
 *     themes driving market performance.
 *
 * These symbols should remain relatively stable over time.
 * New symbols can be added without changing application code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Broad Market Indexes
    |--------------------------------------------------------------------------
    |
    | Core benchmarks used to evaluate overall market direction.
    |
    */

    'indexes' => [

        'SPY' => 'S&P 500',

        'QQQ' => 'Nasdaq 100',

        'DIA' => 'Dow Jones Industrial Average',

        'IWM' => 'Russell 2000',

    ],

    /*
    |--------------------------------------------------------------------------
    | Sector ETFs
    |--------------------------------------------------------------------------
    |
    | Used to evaluate sector rotation and identify areas of strength
    | and weakness within the market.
    |
    | SOXX is intentionally included here because semiconductor
    | leadership has become one of the most important indicators
    | of market strength in recent years.
    |
    */

    'sectors' => [

        'SOXX' => 'Semiconductors',

        'XLK' => 'Technology',

        'XLF' => 'Financials',

        'XLV' => 'Healthcare',

        'XLY' => 'Consumer Discretionary',

        'XLP' => 'Consumer Staples',

        'XLU' => 'Utilities',

        'XLE' => 'Energy',

        'XLI' => 'Industrials',

        'XLRE' => 'Real Estate',

        'XLB' => 'Materials',

        'XLC' => 'Communication Services',

    ],

    /*
    |--------------------------------------------------------------------------
    | Treasury / Bond ETFs
    |--------------------------------------------------------------------------
    |
    | Used to determine risk appetite, duration preference,
    | and investor positioning within fixed income.
    |
    */

    'bonds' => [

        'TLT' => 'Long-Term Treasuries',

        'IEF' => 'Intermediate-Term Treasuries',

        'SHY' => 'Short-Term Treasuries',

    ],

    /*
    |--------------------------------------------------------------------------
    | Volatility Indicators
    |--------------------------------------------------------------------------
    |
    | Used to measure market fear and risk expectations.
    |
    */

    'volatility' => [

        // 'VIX' => 'CBOE Volatility Index',

    ],

    /*
    |--------------------------------------------------------------------------
    | Market Leadership Indicators
    |--------------------------------------------------------------------------
    |
    | These assets help identify which themes are driving
    | institutional and retail participation.
    |
    | SOXX and SMH:
    |     Semiconductor leadership.
    |
    | ARKK:
    |     Speculative growth appetite.
    |
    | IBIT:
    |     Bitcoin and crypto risk appetite.
    |
    | MAGS:
    |     Magnificent Seven concentration and participation.
    |
    */

    'leadership' => [

        'SOXX' => 'Semiconductors',

        'SMH' => 'Semiconductors',

        'ARKK' => 'Disruptive Innovation',

        'IBIT' => 'Bitcoin',

        'MAGS' => 'Magnificent Seven',

    ],

    'ai_signals' => [

        'telemetry' => [

            'history_days' => 250,

        ],

    ],

];
