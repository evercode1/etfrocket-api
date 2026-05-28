<?php

namespace App\Services\Auth;

use App\Models\PasswordResetToken;
use App\Models\User;

class PasswordResetFormService
{
    public function getPasswordResetForm(string $token)
    {
        $message = 'Sorry, we could not find your account.';

        // Check if the token exists

        if (! PasswordResetToken::where('token', $token)->exists()) {

            return response()->json([

                'status' => 'error',

                'message' => 'Something is wrong with your request',

            ], 401);

        }

        $passwordReset = PasswordResetToken::where('token', $token)->first();

        if (! is_null($passwordReset)) {

            // Find and return user

            $requestUserEmail = $passwordReset->email;

            $user = User::where('email', $requestUserEmail)->first();

            if (is_null($user)) {

                // Return a response if the user is not found

                return response()->json([

                    'status' => 'error',

                    'message' => $message,

                ], 200);

            }

            return response()->json([

                'message' => 'User found',
                'user_id' => $user->id,
                'token' => $token,

            ], 201);

        }

        return response()->json([

            'message' => $message,

        ], 200);

    }
}
