<?php

namespace App\Utilities;

use App\Models\PersonalAccessToken;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;

class Auth
{
    // Avoids having to type auth()->id() and auth()->user() everywhere, so we can use Auth::id() and Auth::user() instead.

    public static function id()
    {

        // keeps the ide from complaining
        /** @var Guard|StatefulGuard $auth */
        $auth = auth();

        return $auth->id();
    }

    public static function user()
    {

        // keeps the ide from complaining
        /** @var Guard|StatefulGuard $auth */
        $auth = auth();

        return $auth->user();
    }

    public static function logout()
    {

        $user_id = Auth::id();

        // delete all tokens for user

        PersonalAccessToken::where('tokenable_id', $user_id)->delete();

        return response()->json([

            'status' => 'success',
            'message' => 'Logged out',

        ]);
    }
}
