<?php

namespace App\Services\Auth;

use App\Mail\VerifyEmail;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Support\Facades\Mail;

class RequestVerificationService
{
    public function requestVerification($request)
    {

        /*
        |--------------------------------------------------------------------------
        | Set Values
        |--------------------------------------------------------------------------
        */

        $email = $request->email;

        $user = User::where('email', $email)->first();

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if (! isset($user)) {

            return ['message' => 'your email was not found in our system', 401];
        }

        if (! is_null($user->email_verified_at)) {

            return ['message' => 'You are already verified', 201];
        }

        // need to check if token already exists, if so delete

        $token = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $user_id = $user->id;

        if (UserVerification::where('user_id', $user_id)->exists()) {

            $oldToken = UserVerification::where('user_id', $user_id)->first();

            $oldToken->delete();

        }

        /*
        |--------------------------------------------------------------------------
        | Create User Verification record to store new token
        |--------------------------------------------------------------------------
        */

        UserVerification::create([
            'user_id' => $user->id,
            'token' => $token,
        ]);

        $email = $user->email;

        /*
        |--------------------------------------------------------------------------
        | Send email with new token
        |--------------------------------------------------------------------------
        */

        Mail::to($email)->send(new VerifyEmail($user, $token));

        /*
        |--------------------------------------------------------------------------
        | Browser Feedback
        |--------------------------------------------------------------------------
        */

        return ['message' => 'email has been sent', 201];

    }
}
