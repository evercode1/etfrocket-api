<?php

namespace App\Services\Settings;

class EditEmailFormConfigService
{
    public static function getEmailFormConfig()
    {

        $form_config = [

            [

                'name' => 'email',
                'type' => 'email',
                'label' => 'Email Address',
                'required' => 1,
                'max_length' => 50,
                'instructions' => '',

            ],

            [

                'name' => 'email_confirmation',
                'type' => 'email',
                'label' => 'Confirm Email Address',
                'required' => 1,
                'max_length' => 50,
                'instructions' => '',

            ],

        ];

        // return json response with form config and post endpoint

        return response()->json([

            'form_config' => $form_config,
            'post_endpoint' => 'update-my-email',

        ], 200);

    }
}
