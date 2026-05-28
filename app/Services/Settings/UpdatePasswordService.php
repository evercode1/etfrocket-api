<?php

namespace App\Services\Settings;

use App\Models\User;
use App\Utilities\Auth;
use Illuminate\Http\Request;

class UpdatePasswordService
{
    public static function updatePassword(Request $request)
    {

        $user_id = Auth::id();

        $user = User::where('id', $user_id)->first();

        $user->password = $request->input('password');

        $user->save();

        // return json response with success message

        return response()->json([

            'status' => 'success',
            'message' => 'your password has been updated.'],

            200);

    }
}
