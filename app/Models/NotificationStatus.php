<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationStatus extends Model
{

    use HasFactory;

    const SENT = 1;
    const PREVIOUSLY_SENT = 2;
    const NOTHING_TO_SEND = 3;

    protected $fillable = ['notification_status_name'];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d'

        ];
    }

    public static function GetNotificationStatusId($name)
    {

        $notificationStatus = self::where('notification_status_name', $name)

            ->first();

        return $notificationStatus->id;
    }
}
