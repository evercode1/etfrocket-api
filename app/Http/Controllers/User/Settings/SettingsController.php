<?php

namespace App\Http\Controllers\User\Settings;

use App\Http\Controllers\Controller;
use App\Services\Settings\EditEmailFormConfigService;
use App\Services\Settings\EditUserNameFormConfigService;
use App\Services\Settings\UpdateEmailService;
use App\Services\Settings\UpdatePasswordService;
use App\Services\Settings\UpdateUserNameService;
use App\Utilities\Auth;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function getMySettings()
    {

        $user = Auth::user();

        // return json response with user settings data

        return response()->json([

            'email' => $user->email,
            'name' => $user->name,

        ], 200);
    }

    public function editEmailFormConfig()
    {

        return EditEmailFormConfigService::getEmailFormConfig();
    }

    public function editUserNameFormConfig()
    {

        return EditUserNameFormConfigService::getUserNameFormConfig();
    }

    public function updateMyEmail(Request $request)
    {

        return UpdateEmailService::updateEmail($request);
    }

    public function updateMyUserName(Request $request)
    {

        return UpdateUserNameService::updateUserName($request);
    }

    public function updatePassword(Request $request)
    {

        if ($request->input('password') != $request->input('password_confirmation')) {

            // return json response with error message

            return

                response()->json(
                    [

                        'status' => 'error',
                        'message' => 'password and password confirmation do not match.',
                    ],
                    422
                );
        }

        $request->validate([

            'password' => 'string|required',
            'password_confirmation' => 'string|required',

        ]);

        return UpdatePasswordService::updatePassword($request);
    }
}
