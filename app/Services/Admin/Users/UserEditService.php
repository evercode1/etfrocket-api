<?php

namespace App\Services\Admin\Users;

use App\Models\User;
use App\Utilities\DropDownListMaker;

class UserEditService
{
    public DropDownListMaker $listMaker;

    public function __construct()
    {

        $this->listMaker = new DropDownListMaker;
    }

    public function getFormConfigs(int $id)
    {

        // set values

        $user = User::find($id);

        // set form configs

        $formConfigs = $this->formConfigs();

        // set empty array to hold mutated data

        $form_config_data = [];

        // iterate over each config and format selects with dropdown values
        // the listmaker service that was handed in will retrieve our select values
        // since we're editing, we need old values to display to user

        foreach ($formConfigs as $config) {

            if ($config['type'] == 'select') {

                $column = $config['name'];

                $old_option_value = $user->$column;

                if ($old_option_value == 0) {

                    continue;
                }

                $select_list = $this->listMaker->getSelectsFromLabelName($config['label']);

                $old_option_text = $select_list[$old_option_value];

                $config = array_merge(

                    $config,

                    [

                        'old_option_value' => $old_option_value,
                        'old_option_text' => $old_option_text,
                        'selects' => $select_list,

                    ]

                );

                $form_config_data[] = $config;

            } else {

                $column = $config['name'];

                $old_value = $user->$column;

                $config = array_merge($config, ['old_value' => $old_value]);

                $form_config_data[] = $config;

            }
        }

        // return data

        return [

            'section_heading' => 'Edit User',
            'request_type' => 'post',
            'post_endpoint' => 'manage-user/'.$user->id,
            'form_configs' => $form_config_data,

        ];
    }

    public function formConfigs()
    {

        return [

            [

                'name' => 'name',
                'type' => 'text',
                'label' => 'Name',
                'required' => 1,
                'max_length' => 50,
                'default_value' => '',
                'instructions' => '',
            ],

            [

                'name' => 'email',
                'type' => 'text',
                'label' => 'Email',
                'required' => 1,
                'max_length' => 50,
                'default_value' => '',
                'instructions' => '',

            ],

            [

                'name' => 'is_active',
                'type' => 'boolean',
                'label' => 'Is Active',
                'required' => 1,
                'max_length' => 50,
                'default_value' => '',
                'instructions' => '',

            ],

            [

                'name' => 'is_influencer',
                'type' => 'boolean',
                'label' => 'Is Influencer',
                'required' => 1,
                'max_length' => 50,
                'default_value' => '',
                'instructions' => '',

            ],

            [

                'name' => 'is_subscriber',
                'type' => 'boolean',
                'label' => 'Is Subscriber',
                'required' => 1,
                'max_length' => 50,
                'default_value' => '',
                'instructions' => '',

            ],

            [

                'name' => 'is_admin',
                'type' => 'boolean',
                'label' => 'Is Admin',
                'required' => 1,
                'max_length' => 50,
                'default_value' => '',
                'instructions' => '',

            ],

        ];
    }
}
