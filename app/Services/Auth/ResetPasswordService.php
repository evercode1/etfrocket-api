<?php

namespace App\Services\Auth;

use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetPasswordService
{
    public function passwordReset($request)
    {

        $request->validate([

            'password' => 'required|string|confirmed',
            'email' => 'required|email',
            'token' => 'required|string',

        ]);

        $passwordReset = PasswordResetToken::where('token', $request->token)->first();

        $email = $request->email;

        $user = User::where('email', $email)->first();

        if ($passwordReset->email !== $user->email) {

            return response()->json([

                'status' => 'error',
                'message' => 'invalid credentials',

            ], 401);
        }

        $password = Hash::make($request->password);

        // eloquent wouldn't update password, had to use DB

        DB::table('users')

            ->where('email', $email)

            ->update(['password' => $password]);

        // delete token

        $oldToken = $request->token;

        if (PasswordResetToken::where('token', $oldToken)->exists()) {

            $oldToken = PasswordResetToken::where('token', $oldToken)->first();

            $oldToken->delete();
        }

        return response()->json(['message' => 'Your Password has been updated'], 201);
    }
}
