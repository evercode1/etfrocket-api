<?php

namespace App\Models;

use Database\Factories\SecurityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Security extends Model
{
    /** @use HasFactory<SecurityFactory> */
    use HasFactory;

    protected $fillable = [

        'symbol',
        'security_type_id',
        'status_id',

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

        return self::query()

            ->orderBy('symbol', 'asc')

            ->get(['id', 'symbol'])

            ->map(function ($security) {

                return [

                    'id' => $security->id,

                    'symbol' => $security->symbol,

                ];

            })

            ->values();

    }

    public function detail(): HasOne
    {
        return $this->hasOne(SecurityDetail::class, 'security_id', 'id');
    }

    public function securityType()
    {
        return $this->belongsTo(
            SecurityType::class
        );
    }

    public function status()
    {
        return $this->belongsTo(
            Status::class
        );
    }

    public function updateSchedules()
    {
        return $this->hasMany(
            SecurityUpdateSchedule::class
        );
    }
}
