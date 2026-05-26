<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{

    use HasFactory;

    const DUMPED = 1;
    const PENDING_EMAIL_VERIFICATION = 2;
    const PENDING = 3;
    const ACTIVE = 4;
    const REJECTED = 5;
    const WHITELIST = 6;
    const SUBMITTED = 7;
    const UNDER_REVIEW = 8;
    const APPROVED = 9;
    const OPEN = 10;
    const CLOSED = 11;
    const RUNNING = 12;
    const COMPLETED = 13;
    const INACTIVE = 14;
    const SUCCESS = 15;
    const FAILED = 16;
    const PROCESSING = 17;


    protected $fillable = [

        'status_name'

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d'

        ];
    }

    public static function getStatusId(string $name)
    {

        $status = self::where('status_name', $name)->first();

        return $status->id;
    }

    public static function setSuccessStatusId(int $success)
    {

        // for crons, we need to determine if the cron ran successfully
        // and return the correct status

        if ($success == 1) {

            return self::getStatusId('completed');
        }

        return self::getStatusId('failed');
    }

    public static function getStatusNameFromId(int $id)
    {

        $status = self::find($id);

        return $status->status_name;
    }
}
