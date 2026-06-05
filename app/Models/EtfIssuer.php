<?php

namespace App\Models;

use Database\Factories\EtfFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtfIssuer extends Model
{
    /** @use HasFactory<EtfFactory> */
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

    const TAPPALPHA = 12;

    protected $fillable = [

        'etf_issuer_name',
        'website_url',
        'status_id',
        'notes',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];
    }

    public static function getSelects()
    {

        return self::orderBy('etf_issuer_name', 'asc')

            ->pluck('etf_issuer_name', 'id');
    }

    public function status()
    {

        return $this->belongsTo(

            Status::class,

            'status_id'

        );

    }
}
