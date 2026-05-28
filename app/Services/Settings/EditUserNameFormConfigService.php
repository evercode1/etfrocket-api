<?php

namespace App\Services\Settings;

class EditUserNameFormConfigService
{
    public static function getUserNameFormConfig()
    {

        $form_config = [

            [

                'name' => 'name',
                'type' => 'text',
                'label' => 'Username',
                'required' => 1,
                'max_length' => 50,
                'instructions' => '',

            ],

        ];

        return [

            'form_config' => $form_config,
            'post_endpoint' => 'update-my-user-name',

        ];

    }
}
