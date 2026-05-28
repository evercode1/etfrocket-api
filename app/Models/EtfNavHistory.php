<?php

namespace App\Models;

use Database\Factories\EtfNavHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtfNavHistory extends Model
{
    /** @use HasFactory<EtfNavHistoryFactory> */
    use HasFactory;

    protected $fillable = [

        'etf_id',
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
