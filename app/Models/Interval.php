<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interval extends Model
{

    use HasFactory;

    protected $fillable = ['interval_name'];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d'

        ];
    }

    public static function getIntervalId($name)
    {

        $interval = self::where('interval_name', $name)

            ->first();

        return $interval->id;
    }

    public static function getSelects()
    {

        return self::orderBy('interval_name', 'asc')

            ->pluck('interval_name', 'id');
    }
}
