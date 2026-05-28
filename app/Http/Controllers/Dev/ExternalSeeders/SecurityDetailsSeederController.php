<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\SecurityDetail;

class SecurityDetailsSeederController extends Controller
{
    public function run(): void
    {

        SecurityDetail::truncate();

        // all the securites and create them in the database

    }
}
