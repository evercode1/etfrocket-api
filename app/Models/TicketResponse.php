<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketResponse extends Model
{
    use HasFactory;

    protected $fillable = [

        'support_topic_id',
        'support_ticket_id',
        'user_id',
        'response_text',
        'is_from_customer',
        'is_read',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];

    }

    public function supportTicket()
    {

        return $this->belongsTo(SupportTicket::class);

    }
}
