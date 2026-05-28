<?php

namespace App\Services\Auth;

use App\Mail\VerifyEmail;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LoginService
{
    public function handleLogin($fields)
    {
        // Check email and get user

        $user = User::where('email', $fields['email'])

            ->first();

        // Check user is valid and password matches

        if (! isset($user) || ! Hash::check($fields['password'], $user->password)) {

            return response()->json([

                'status' => 'error',
                'message' => 'Bad credentials',

            ], 401);
        }

        // set user_id

        $user_id = $user->id;

        // if user is not active, return not allowed

        if (! $user->is_active == 1) {

            return response()->json([

                'status' => 'error',
                'message' => 'Bad credentials',

            ], 401);
        }

        // Check if Email is Verified
        if ($user->email_verified_at === null) {

            // 1. Generate a verification token (a random string for the URL)
            $verificationToken = Str::random(60);

            // 2. Save to your user_verifications table

            UserVerification::updateOrCreate(
                ['user_id' => $user->id],
                ['token' => $verificationToken, 'created_at' => now()]
            );

            // 3. Send the email (This satisfies your Mail::assertSent check)

            Mail::to($user->email)->send(new VerifyEmail($user, $verificationToken));

            // 4. Return the specific message your test is looking for
            return response()->json([
                'message' => 'Please verify your account.',
            ], 200);
        }

        // clear out any old access tokens

        if (PersonalAccessToken::where('tokenable_id', $user_id)->exists()) {

            PersonalAccessToken::where('tokenable_id', $user_id)->delete();
        }

        // create new access token

        $token = $user->createToken('defttoken')->plainTextToken;

        // format response data

        $responseData = [

            'user' => $user->makeHidden([
                'password',
                'remember_token',
                'email_verified_at',
                'created_at',
                'updated_at',
                'stripe_id',
                'pm_type',
                'pm_last_four',
            ]),

            'token' => $token,

        ];

        // feedback to the browser

        return response()->json($responseData, 200);
    }
}
