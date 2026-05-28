<?php

namespace App\Services\Auth;

use App\Services\FailureLogs\LogFailureService;

class RegistrationService
{
    public function handleRegistration($request)
    {

        try {

            // use app container to call class and bind dependencies

            $user = app(RegistrationTransactionService::class)->createUser($request);

            // create new access token

            $token = $user->createToken('deftdata')->plainTextToken;
        } catch (\Exception $e) {

            (new LogFailureService)->logFailure($e, 'register_user', __CLASS__);

            return response()->json([

                'status' => 'error',

                'message' => 'Oops! Something went wrong. Please try again later or contact support if the issue persists.',

            ], 500);
        }

        return response()->json([

            'status' => 'success',
            'user' => $user,
            'message' => 'please confirm your email',

        ], 201);
    }
}
