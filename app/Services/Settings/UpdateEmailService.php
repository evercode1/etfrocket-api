<?php

namespace App\Services\Settings;

use App\Models\User;
use App\Utilities\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateEmailService
{
    public static function updateEmail(Request $request)
    {

        $id = Auth::id();

        if ($request->input('email') != $request->input('email_confirmation')) {

            // json response with error message if email and email confirmation do not match

            return response()->json(

                [
                    'status' => 'error',
                    'message' => 'email and email confirmation do not match.',

                ], 422

            );

        }

        $request->validate([

            'email' => [

                'required',
                'email',
                Rule::unique('users')->ignore($id),
            ],

        ]);

        $user = User::where('id', $id)->first();

        $user->email = $request->input('email');

        $user->save();

        return response()->json(

            [
                'status' => 'success',
                'message' => 'your email has been updated.',

            ], 200

        );

    }
}
