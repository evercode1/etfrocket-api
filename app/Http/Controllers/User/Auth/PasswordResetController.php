<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordResetFormService;
use App\Services\Auth\RequestPasswordResetTokenService;
use App\Services\Auth\ResetPasswordService;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    public function requestPasswordResetToken(Request $request, RequestPasswordResetTokenService $service)
    {

        $request->validate([

            'email' => 'required|email',

        ]);

        return $service->requestResetToken($request);

    }

    public function getPasswordResetForm(string $token, PasswordResetFormService $service)
    {

        return $service->getPasswordResetForm($token);

    }

    public function passwordReset(Request $request, ResetPasswordService $service)
    {

        return $service->passwordReset($request);

    }
}
