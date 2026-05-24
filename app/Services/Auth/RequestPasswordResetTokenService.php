<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\PasswordResetToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPasswordEmail;
use Illuminate\Http\Request;

class RequestPasswordResetTokenService
{

    public function requestResetToken(Request $request)
    {

        $user = User::where('email', $request->email)->first();

        if (! isset($user)) {

            return response()->json([

                'status' => 'error',

                'message' => 'your email was not found in our system'

            ], 401);
        }

        // get the email address of user

        $email = $user->email;

        // need to check if token already exists, if so delete

        if (PasswordResetToken::where('email', $email)->exists()) {

            PasswordResetToken::where('email', $email)->delete();
        }

        $token = Str::random(64);

        PasswordResetToken::create([

            'email' => $email,
            'token' => $token,
            'created_at' => now()

        ]);

        Mail::to($email)->send(new ForgotPasswordEmail($token, $email));

        return response()->json([

            'message' => 'email has been sent'
        ], 201);
    }
}
