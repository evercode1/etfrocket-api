<?php

namespace App\Services\Support;

class ReplyFormConfigService
{
    public static function getReplyFormConfig()
    {

        $form_config = [

            [
                'name' => 'response_text',
                'type' => 'textarea',
                'label' => 'Response Text',
                'required' => 1,
                'max_length' => 50,
                'instructions' => '',

            ],

        ];

        return response()->json([

            ' status' => 'success',
            'section_heading' => 'Reply To Ticket',
            'action_type' => 'post',
            'post_endpoint' => 'support-reply-to-ticket',
            'form_config' => $form_config,

        ], 200);
    }
}
