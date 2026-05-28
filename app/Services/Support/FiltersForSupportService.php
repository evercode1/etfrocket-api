<?php

namespace App\Services\Support;

class FiltersForSupportService
{
    public static function getFilters()
    {

        $form_config = [

            [

                'name' => 'ticket_status',
                'type' => 'select',
                'label' => 'Ticket Status',
                'options' => [

                    1 => 'all',
                    2 => 'closed',
                    3 => 'open',

                ],

                'required' => 1,
                'max_length' => 50,
                'default_value' => '1',
                'instructions' => '',

            ],

        ];

        return response()->json([

            'status' => 'success',
            'section_heading' => 'Choose Ticket Status',
            'action_type' => 'get',
            'table_data_endpoint' => 'get-support-tickets',
            'form_config' => $form_config,

        ], 200);

    }
}
