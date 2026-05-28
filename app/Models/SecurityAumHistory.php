<?php

namespace App\Models;

use Database\Factories\SecurityAumHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityAumHistory extends Model
{
    /** @use HasFactory<SecurityAumHistoryFactory> */
    use HasFactory;

    protected $fillable = [

        'security_id',
        'aum_date',
        'assets_under_management',
        'data_source_id',
        'retrieved_at',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',
            'aum_date' => 'date:Y-m-d',
            'retrieved_at' => 'date:Y-m-d',

        ];
    }
}
