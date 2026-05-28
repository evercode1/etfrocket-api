<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\Security;

class SecuritiesSeederController extends Controller
{
    public function run(): void
    {

        Security::truncate();

        // all the securites and create them in the database

    }
}
