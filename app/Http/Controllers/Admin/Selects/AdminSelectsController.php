<?php

namespace App\Http\Controllers\Admin\Selects;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
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

    public function show(
        string $key
    ) {
        try {

            $config =

                config(
                    "admin_selects.{$key}"
                );

            if (

                ! $config

            ) {

                return response()->json([

                    'success' => false,

                    'message' => 'Select configuration not found.',

                ], 404);

            }

            $model =

                $config['model'];

            $nameColumn =

                $config['name_column'];

            $rows =

                $model::query()

                    ->orderBy(
                        $nameColumn
                    )

                    ->get()

                    ->map(

                        function ($row) use (

                            $nameColumn

                        ) {

                            return [

                                'id' => $row->id,

                                'name' => $row->{$nameColumn},

                            ];

                        }

                    )

                    ->values();

            return response()->json([

                'success' => true,

                'config' => [

                    'key' => $config['key'],

                    'label' => $config['label'],

                    'description' => $config['description'],

                    'allow_create' => $config['allow_create'],

                    'allow_update' => $config['allow_update'],

                    'allow_delete' => $config['allow_delete'],

                ],

                'rows' => $rows,

            ]);

        } catch (\Exception $e) {

            Log::error(

                'Failed to load admin select',

                [

                    'key' => $key,

                    'error' => $e->getMessage(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to load select.',

            ], 500);

        }
    }

    public function store(
        Request $request,
        string $key
    ) {

        $request->validate([

            'name' => [

                'required',

                'string',

                'max:255',

            ],

        ]);
        try {

            $config =

                config(
                    "admin_selects.{$key}"
                );

            if (

                ! $config

            ) {

                return response()->json([

                    'success' => false,

                    'message' => 'Select configuration not found.',

                ], 404);

            }

            if (

                ! $config['allow_create']

            ) {

                return response()->json([

                    'success' => false,

                    'message' => 'Creation is not allowed for this select.',

                ], 403);

            }

            $model =

                $config['model'];

            $nameColumn =

                $config['name_column'];

            $record =

                $model::create([

                    $nameColumn => $request->input(
                        'name'
                    ),

                ]);

            return response()->json([

                'success' => true,

                'data' => [

                    'id' => $record->id,

                    'name' => $record->{$nameColumn},

                ],

            ]);

        } catch (\Exception $e) {

            Log::error(

                'Failed to create admin select value',

                [

                    'key' => $key,

                    'error' => $e->getMessage(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to create select value.',

            ], 500);

        }
    }

    public function update(
        Request $request,
        string $key,
        int $id
    ) {
        $request->validate([

            'name' => [

                'required',

                'string',

                'max:255',

            ],

        ]);

        try {

            $config =

                config(
                    "admin_selects.{$key}"
                );

            if (

                ! $config

            ) {

                return response()->json([

                    'success' => false,

                    'message' => 'Select configuration not found.',

                ], 404);

            }

            if (

                ! $config['allow_update']

            ) {

                return response()->json([

                    'success' => false,

                    'message' => 'Updating is not allowed for this select.',

                ], 403);

            }

            $model =

                $config['model'];

            $nameColumn =

                $config['name_column'];

            $record =

                $model::findOrFail(
                    $id
                );

            $record->update([

                $nameColumn => $request->input(
                    'name'
                ),

            ]);

            return response()->json([

                'success' => true,

                'data' => [

                    'id' => $record->id,

                    'name' => $record->{$nameColumn},

                ],

            ]);

        } catch (ModelNotFoundException $e) {

            return response()->json([

                'success' => false,

                'message' => 'Select value not found.',

            ], 404);

        } catch (\Exception $e) {

            Log::error(

                'Failed to update admin select value',

                [

                    'key' => $key,

                    'id' => $id,

                    'error' => $e->getMessage(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to update select value.',

            ], 500);

        }
    }

    public function destroy(
        string $key,
        int $id
    ) {
        try {

            $config =

                config(
                    "admin_selects.{$key}"
                );

            if (

                ! $config

            ) {

                return response()->json([

                    'success' => false,

                    'message' => 'Select configuration not found.',

                ], 404);

            }

            if (

                ! $config['allow_delete']

            ) {

                return response()->json([

                    'success' => false,

                    'message' => 'Deleting is not allowed for this select.',

                ], 403);

            }

            $model =

                $config['model'];

            $record =

                $model::findOrFail(
                    $id
                );

            $record->delete();

            return response()->json([

                'success' => true,

            ]);

        } catch (ModelNotFoundException $e) {

            return response()->json([

                'success' => false,

                'message' => 'Select value not found.',

            ], 404);

        } catch (\Exception $e) {

            Log::error(

                'Failed to delete admin select value',

                [

                    'key' => $key,

                    'id' => $id,

                    'error' => $e->getMessage(),

                ]

            );

            return response()->json([

                'success' => false,

                'message' => 'Failed to delete select value.',

            ], 500);

        }
    }
}
