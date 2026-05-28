<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [

        'support_topic_id',
        'status_id',
        'user_id',
        'ticket_text',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];

    }

    public function ticketResponses()
    {

        return $this->hasMany(TicketResponse::class);

    }
}
