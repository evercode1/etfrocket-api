<?php

namespace App\Models;

use Database\Factories\SecurityNavHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityNavHistory extends Model
{
    /** @use HasFactory<SecurityNavHistoryFactory> */
    use HasFactory;

    protected $fillable = [

        'security_id',
        'nav_date',
        'nav_per_share',
        'data_source_id',
        'retrieved_at',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',
            'nav_date' => 'date:Y-m-d',
            'retrieved_at' => 'date:Y-m-d',

        ];
    }
}
