<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\AiMarketSignal;
use Carbon\Carbon;

class AiMarketSignalsSeederController extends Controller
{
    public function run(): void
    {
        AiMarketSignal::truncate();

        AiMarketSignal::create([

            'signal_type_id' => 1,

            'title' =>
            'AI Market Snapshot',

            'subtitle' =>
            'Daily AI-generated overview of market sentiment and macro positioning.',

            'market_mood' =>
            'Risk-On',

            'confidence_score' =>
            84,

            'markdown_content' => <<<MARKDOWN
# Market Snapshot

Markets pushed higher today as treasury yields stabilized and volatility continued to compress.

## Key Signals

- Nasdaq leadership remains strong
- Bitcoin continues consolidating above breakout levels
- Treasury yields softened slightly
- Breadth participation improved

## AI Interpretation

Current market conditions favor:
- momentum continuation
- growth exposure
- income strategy participation

Risk appetite remains elevated while defensive positioning continues weakening.
MARKDOWN,

            'payload_json' => [

                'market_status' =>
                'OPEN',

                'volatility' =>
                'declining',

                'breadth' =>
                'improving',

            ],

            'generated_at' =>
            Carbon::now(),

            'expires_at' =>
            Carbon::now()->addDay(),

            'is_active' => true,

            'ai_model' =>
            'gpt-4.1-mini',

        ]);

        AiMarketSignal::create([

            'signal_type_id' => 2,

            'title' =>
            'AI Market Conditions',

            'subtitle' =>
            'AI interpretation of volatility, momentum, and market behavior.',

            'market_mood' =>
            'Neutral',

            'confidence_score' =>
            76,

            'markdown_content' => <<<MARKDOWN
# Market Conditions

Markets remain in a transitional phase between defensive positioning and renewed growth participation.

## Current Readings

- Volatility declining
- Bond market stabilizing
- Growth participation improving
- Defensive sectors lagging

## AI Interpretation

The AI system currently identifies:
- moderate bullish momentum
- improving participation
- reduced macro fear
- elevated event sensitivity

Momentum conditions remain constructive but fragile.
MARKDOWN,

            'payload_json' => [

                'market_status' =>
                'OPEN',

                'vix_trend' =>
                'declining',

                'bond_market' =>
                'stable',

            ],

            'generated_at' =>
            Carbon::now(),

            'expires_at' =>
            Carbon::now()->addDay(),

            'is_active' => true,

            'ai_model' =>
            'gpt-4.1-mini',

        ]);

        AiMarketSignal::create([

            'signal_type_id' => 3,

            'title' =>
            'AI Market Events',

            'subtitle' =>
            'Upcoming catalysts and macro events impacting financial markets.',

            'market_mood' =>
            'Event Driven',

            'confidence_score' =>
            91,

            'markdown_content' => <<<MARKDOWN
# Upcoming Market Events

Several major macro catalysts are approaching this week.

## This Week

- Federal Reserve commentary
- Treasury auctions
- Large-cap earnings releases
- Employment data publication

## AI Interpretation

The AI system expects elevated market sensitivity surrounding:
- interest rates
- liquidity conditions
- earnings guidance
- macro surprises

Markets may experience elevated short-term volatility during key releases.
MARKDOWN,

            'payload_json' => [

                'market_status' =>
                'OPEN',

                'fed_event' =>
                true,

                'earnings_week' =>
                true,

                'employment_data' =>
                true,

            ],

            'generated_at' =>
            Carbon::now(),

            'expires_at' =>
            Carbon::now()->addDay(),

            'is_active' => true,

            'ai_model' =>
            'gpt-4.1-mini',

        ]);
    }
}
