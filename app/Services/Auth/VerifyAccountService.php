<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserVerification;

class VerifyAccountService
{
    public function verifyAccount(string $token)
    {
        /*
        |--------------------------------------------------------------------------
        | Default message and set datetime
        |--------------------------------------------------------------------------
        */

        $message = 'Sorry your email cannot be identified.';

        $datetime = now();

        /*
        |--------------------------------------------------------------------------
        | Validate Token
        |--------------------------------------------------------------------------
        */

        if (! UserVerification::where('token', $token)->exists()) {

            return ['message' => 'Token not found, try requesting the token again.'];
        }

        // Find user by token
        // call UserVerification instance using token

        $verifyUser = UserVerification::where('token', $token)->first();

        if (! $verifyUser || ! $verifyUser->user) {

            return ['message' => 'User not found'];
        }

        // use relationship to get user instance

        $user = $verifyUser->user;

        $user_id = $user->id;

        // check to see if the user is not null

        if (! isset($user_id)) {

            return ['message' => 'User not found'];
        }

        /*
        |--------------------------------------------------------------------------
        | confirm user exists and finish verification
        |--------------------------------------------------------------------------
        */

        if (User::where('id', $user_id)->exists()) {

            // if user email_verified_at is null, confirm user and return message
            if (is_null($user->email_verified_at)) {

                $message = $this->confirmUser($user, $datetime);

            } else {

                UserVerification::where('user_id', $user_id)->delete();
                $message = 'Your e-mail is already verified. You may now login.';

            }

            return ['message' => $message];

        }

    }

    public function confirmUser(User $user, string $datetime)
    {

        $user->email_verified_at = $datetime;
        $user->is_active = 1;

        $user->save();

        // remove token

        UserVerification::where('user_id', $user->id)->delete();

        return 'Your e-mail is verified. You may now login.';
    }
}
