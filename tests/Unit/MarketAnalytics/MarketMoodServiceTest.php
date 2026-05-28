<?php

namespace Tests\Unit\MarketAnalytics;

use App\Services\AI\MarketAnalytics\MarketMoodService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketMoodServiceTest extends TestCase
{
    private MarketMoodService $service;

    protected function setUp(): void
    {

        parent::setUp();

        $this->service =

            app(

                MarketMoodService::class

            );
    }

    public function test_it_returns_euphoric_market_mood()
    {

        Http::fake([

            'https://api.openai.com/*' => Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' => 'Euphoric',

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);

        $result =

            $this->service
                ->determine();

        $this->assertEquals(

            'Euphoric',

            $result['market_mood']

        );

        $this->assertEquals(

            95,

            $result['confidence_score']

        );
    }

    public function test_it_returns_bullish_market_mood()
    {

        Http::fake([

            'https://api.openai.com/*' => Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' => 'Bullish',

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);

        $result =

            $this->service
                ->determine();

        $this->assertEquals(

            'Bullish',

            $result['market_mood']

        );

        $this->assertEquals(

            88,

            $result['confidence_score']

        );
    }

    public function test_it_returns_neutral_market_mood()
    {

        Http::fake([

            'https://api.openai.com/*' => Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' => 'Neutral',

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);

        $result =

            $this->service
                ->determine();

        $this->assertEquals(

            'Neutral',

            $result['market_mood']

        );

        $this->assertEquals(

            70,

            $result['confidence_score']

        );
    }

    public function test_it_returns_undetermined_when_response_is_invalid()
    {

        Http::fake([

            'https://api.openai.com/*' => Http::response([

                'output' => [

                    [

                        'content' => [

                            [

                                'text' => 'INVALID_RESPONSE',

                            ],

                        ],

                    ],

                ],

            ], 200),

        ]);

        $result =

            $this->service
                ->determine();

        $this->assertEquals(

            'Undetermined',

            $result['market_mood']

        );

        $this->assertEquals(

            50,

            $result['confidence_score']

        );
    }

    public function test_it_returns_undetermined_when_api_fails()
    {

        Http::fake([

            'https://api.openai.com/*' => Http::response([], 500),

        ]);

        $result =

            $this->service
                ->determine();

        $this->assertEquals(

            'Undetermined',

            $result['market_mood']

        );

        $this->assertEquals(

            50,

            $result['confidence_score']

        );
    }

    public function test_it_returns_undetermined_when_exception_occurs()
    {

        Http::fake(function () {

            throw new \Exception(
                'Connection failed'

            );
        });

        $result =

            $this->service
                ->determine();

        $this->assertEquals(

            'Undetermined',

            $result['market_mood']

        );

        $this->assertEquals(

            50,

            $result['confidence_score']

        );
    }

    public function test_it_returns_allowed_market_moods_only()
    {

        $allowedMoods = [

            'Euphoric',

            'Bullish',

            'Risk-On',

            'Neutral',

            'Risk-Off',

            'Bearish',

            'Panic',

            'Undetermined',

        ];

        foreach ($allowedMoods as $mood) {

            Http::fake([

                'https://api.openai.com/*' => Http::response([

                    'output' => [

                        [

                            'content' => [

                                [

                                    'text' => $mood,

                                ],

                            ],

                        ],

                    ],

                ], 200),

            ]);

            $result =

                $this->service
                    ->determine();

            $this->assertContains(

                $result['market_mood'],

                $allowedMoods

            );
        }
    }
}
