<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSecurityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Security
            |--------------------------------------------------------------------------
            */

            'symbol' => [

                'required',

                'string',

                'max:255',

                'unique:securities,symbol',

            ],

            'security_type_id' => [

                'required',

                'integer',

                'exists:security_types,id',

            ],

            'status_id' => [

                'required',

                'integer',

                'exists:statuses,id',

            ],

            /*
            |--------------------------------------------------------------------------
            | Security Detail
            |--------------------------------------------------------------------------
            */

            'security_name' => [

                'required',

                'string',

                'max:255',

            ],

            'etf_issuer_id' => [

                'nullable',

                'integer',

                'exists:etf_issuers,id',

            ],

            'etf_strategy_type_id' => [

                'nullable',

                'integer',

                'exists:etf_strategy_types,id',

            ],

            'distribution_frequency_id' => [

                'nullable',

                'integer',

                'exists:distribution_frequencies,id',

            ],

            'expense_ratio' => [

                'nullable',

                'numeric',

                'min:0',

            ],

            'website_url' => [

                'nullable',

                'url',

                'max:255',

            ],

            'notes' => [

                'nullable',

                'string',

            ],

            /*
            |--------------------------------------------------------------------------
            | Schedules
            |--------------------------------------------------------------------------
            */

            'schedules' => [

                'required',

                'array',

                'min:1',

            ],

            'schedules.*.security_update_type_id' => [

                'required',

                'integer',

                'exists:security_update_types,id',

            ],

            'schedules.*.run_day' => [

                'required',

                'integer',

                'between:0,7',

            ],

            'schedules.*.run_hour' => [

                'required',

                'integer',

                'between:0,23',

            ],

            'schedules.*.status_id' => [

                'required',

                'integer',

                'exists:statuses,id',

            ],

        ];
    }

    public function messages(): array
    {
        return [

            'symbol.required' => 'A symbol is required.',

            'security_name.required' => 'A security name is required.',

            'schedules.required' => 'At least one update schedule is required.',

            'schedules.min' => 'At least one update schedule is required.',

        ];
    }
}
