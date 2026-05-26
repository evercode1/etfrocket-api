<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EtfIssuer extends Model
{
    /** @use HasFactory<\Database\Factories\EtfFactory> */
    use HasFactory;

    const YIELDMAX = 1;
    const ROUNDHILL = 2;
    const REX = 3;
    const JPMORGAN = 4;
    const GLOBAL_X = 5;
    const DEFIANCE = 6;
    const AMPLIFY = 7;
    const SIMPLIFY = 8;
    const NEOS = 9;
    const KURV = 10;
    const NICHOLASX = 11;

    protected $fillable = [

        'etf_issuer_name',
        'website_url',
        'status_id',
        'notes'

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d'

        ];
    }
}
