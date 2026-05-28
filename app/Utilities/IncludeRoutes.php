<?php

namespace App\Utilities;

class IncludeRoutes
{
    public static function file(string $path)
    {

        include base_path($path);

    }
}
