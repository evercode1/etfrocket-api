<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityUpdateSchedule extends Model
{
    use HasFactory;

    protected $table =
        'security_update_schedules';

    protected $fillable = [

        'security_id',

        'security_update_type_id',

        'run_day',

        'run_hour',

        'last_run_at',

        'status_id',

    ];

    protected $casts = [

        'last_run_at' => 'datetime',

    ];

    public function security()
    {
        return $this->belongsTo(
            Security::class,
            'security_id'
        );
    }

    public function updateType()
    {
        return $this->belongsTo(
            SecurityUpdateType::class,
            'security_update_type_id'
        );
    }

    public function status()
    {

        return $this->belongsTo(

            Status::class

        );

    }
}
