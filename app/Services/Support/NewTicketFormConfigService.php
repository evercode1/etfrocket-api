<?php

namespace App\Services\Support;

use App\Models\SupportTopic;

class NewTicketFormConfigService
{
    public static function getNewTicketFormConfig()
    {

        $form_config = [

            [
                'name' => 'support_topic_id',
                'type' => 'select',
                'label' => 'Support Topic',
                'options' => SupportTopic::getSelects(),
                'required' => 1,
                'max_length' => 50,
                'instructions' => '',
            ],

            [
                'name' => 'ticket_text',
                'type' => 'text',
                'label' => 'Your Issue',
                'required' => 1,
                'max_length' => 2000,
                'instructions' => '',

            ],

        ];

        return response()->json([

            'status' => 'success',
            'form_config' => $form_config,
            'post_endpoint' => 'create-support-ticket',

        ], 200);

    }
}
