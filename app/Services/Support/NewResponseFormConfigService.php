<?php

namespace App\Services\Support;

class NewResponseFormConfigService
{
    public static function getNewResponseFormConfig()
    {

        $form_config = [

            [
                'name' => 'response_text',
                'type' => 'text',
                'label' => 'Your Response',
                'required' => 1,
                'max_length' => 2000,
                'instructions' => '',

            ],

        ];

        return response()->json([

            'status' => 'success',
            'form_config' => $form_config,
            'post_endpoint' => 'respond-to-support-response',

        ], 200);

    }
}
