<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityUpdateType extends Model
{
    use HasFactory;

    const DIVIDEND = 1;

    const FUND_DATA = 2;

    protected $fillable = [

        'security_update_type_name',

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

        return self::orderBy('security_update_type_name', 'asc')

            ->pluck('security_update_type_name', 'id');
    }

    public function schedules()
    {
        return $this->hasMany(
            SecurityUpdateSchedule::class,
            'security_update_type_id'
        );
    }
}
