<?php

namespace App\Services\Auth;

use App\Mail\VerifyEmail;
use App\Models\User;
use App\Models\UserVerification;
use App\Services\FailureLogs\LogFailureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistrationTransactionService
{
    public function createUser($request)
    {

        // Start transaction!

        DB::beginTransaction();

        try {

            // 1. Create the user record

            $user = User::create([

                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'email_verified_at' => null,

            ]);

            // 2. Generate and save verification token

            $verificationToken = Str::random(60);
            UserVerification::create([
                'user_id' => $user->id,
                'token' => $verificationToken,
                'created_at' => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {

            // Rollback transaction

            DB::rollback();

            (new LogFailureService)->logFailure($e, 'register_user', __CLASS__);

            throw new \RuntimeException('Oops! Something went wrong. Please try again later or contact support if the issue persists.');
        }

        // 3. Send the email

        Mail::to($user->email)
            ->send(new VerifyEmail($user, $verificationToken));

        Log::info('Sending email to: '.$user->email);

        return $user;
    }
}
