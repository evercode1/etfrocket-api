<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MakeSeedsController extends Controller
{
    public function index(Request $request)
    {

        if (! env('ALLOW_SEEDS')) {

            return ['message' => 'ALLOW_SEEDS seeds set to false. Check your ENV file.'];
        }

        $models = [

            'AiMarketSignals',
            'DataSources',
            'DistributionFrequency',
            'EtfIssuers',
            'EtfStrategyTypes',
            'HelpArticleCategories',
            'HelpArticles',
            'ImportLogs',
            'ImportTypes',
            'Intervals',
            'MetricDirections',
            'NotificationStatuses',
            'PerformanceRangeTypes',
            'Portfolios',
            'Securities',
            'SecurityAumHistories',
            'SecurityDetails',
            'SecurityTypes',
            'SecurityUpdateSchedules',
            'SecurityUpdateTypes',
            'SignalTypes',
            'Statuses',
            'SupportTopics',
            'TransactionTypes',

        ];

        $count = 0;

        foreach ($models as $model) {

            app("App\Http\Controllers\Dev\ExternalSeeders\\{$model}SeederController")->run();

            $count++;
        }

        return "$count seeds created.";
    }

    public function getSeedFormConfig()
    {

        return response()->json([

            'status' => 'success',
            'form_config' => [

                'name' => 'table_name',
                'type' => 'text',
                'label' => 'Table Name',
                'required' => 1,
                'max_length' => 50,
                'instructions' => 'enter the table name in snake_case for the seeds you want to create',

            ],

            'section_heading' => 'Make Single Table Seeds',
            'request_type' => 'get',
            'get_endpoint' => 'make-seed?seed={table_name}',
            'button_text' => 'Make Single Table Seeds',

        ], 200);
    }

    public function makeSeed(Request $request)
    {

        if (! env('ALLOW_SEEDS')) {

            return ['message' => 'ALLOW_SEEDS seeds set to false. Check your ENV file.'];
        }

        $seed = $request->input('seed');

        // create dynamically
        // seed from request is table name

        // need to convert table name to controller name partial

        $controller_name = Str::studly($seed);

        app("App\Http\Controllers\Dev\ExternalSeeders\\{$controller_name}SeederController")->run();

        $count = DB::table($seed)->count();

        return ['Success' => $count." {$seed} created."];
    }

    public function dropSeed(Request $request)
    {

        if (! env('ALLOW_SEEDS')) {

            return ['message' => 'ALLOW_SEEDS seeds set to false. Check your ENV file.'];
        }

        $table = $request->input('seed');

        DB::table($table)->truncate();

        return ['message' => 'The seed for '.$table.' have been destroyed.'];
    }
}
