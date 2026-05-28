<?php

namespace App\Services\FailureLogs;

use Illuminate\Support\Facades\File;

class LogFailureService
{
    public function logFailure($ex, $log_type, $class)
    {

        $directoryPath = storage_path('TransactionFailureLogs'); // Full path to storage directory

        // check if the directory exists

        if (! File::exists($directoryPath)) {

            // create the directory

            File::makeDirectory($directoryPath, 0755, true);

        }

        // log file path, we only use this if the DB transaction fails

        $log_file = storage_path('TransactionFailureLogs/'.$log_type.'_'.date('Y-m-d-H-i-s').'.log');

        $message = "################ Transaction Failed on $log_type on class $class.";

        $end_message = '########################################################################################'.PHP_EOL.PHP_EOL;

        @file_put_contents($log_file, $message.PHP_EOL.$ex->getMessage().PHP_EOL.$end_message, FILE_APPEND);

    }
}
