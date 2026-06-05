<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEtfIssuerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'etf_issuer_name' => [

                'required',

                'string',

                'max:255',

                'unique:etf_issuers,etf_issuer_name',

            ],

            'website_url' => [

                'nullable',

                'url',

                'max:255',

            ],

            'status_id' => [

                'required',

                'integer',

                'exists:statuses,id',

            ],

            'notes' => [

                'nullable',

                'string',

            ],

        ];
    }

    public function messages(): array
    {
        return [

            'etf_issuer_name.required' => 'ETF issuer name is required.',

            'etf_issuer_name.unique' => 'An ETF issuer with this name already exists.',

            'website_url.url' => 'Website URL must be a valid URL.',

            'status_id.required' => 'Status is required.',

            'status_id.exists' => 'Selected status is invalid.',

        ];
    }
}
