<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\RequestVerificationService;
use App\Services\Auth\VerifyAccountService;
use Illuminate\Http\Request;

class UserVerificationController extends Controller
{
    public function verifyAccount(string $token, VerifyAccountService $service)
    {

        return $service->verifyAccount($token);

    }

    public function requestVerificationToken(Request $request, RequestVerificationService $service)
    {

        return $service->requestVerification($request);

    }
}
