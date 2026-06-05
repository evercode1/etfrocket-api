<?php

namespace App\Http\Controllers\Admin\Selects;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class AdminSelectsController extends Controller
{
    public function index()
    {
        try {

            $selects =

                collect(

                    config(
                        'admin_selects'
                    )

                )

                    ->map(function (

                        array $config

                    ) {

                        return [

                            'key' => $config['key'],

                            'label' => $config['label'],

                            'description' => $config['description'],

                            'allow_create' => $config['allow_create'],

                            'allow_update' => $config['allow_update'],

                            'allow_delete' => $config['allow_delete'],

                        ];

                    })

                    ->sortBy(

                        'label'

                    )

                    ->values();

            return response()->json([

                'success' => true,

                'data' => $selects,

            ]);

        } catch (\Exception $e) {

            Log::error(

                'Failed to load admin selects',

                [

                    'error' => $e->getMessage(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to load admin selects.',

            ], 500);

        }
    }
}
