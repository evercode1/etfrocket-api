<?php

namespace App\Queries\Support;

use App\Models\SupportTicket;
use App\Utilities\Auth;

class ShowSupportTicketQuery
{
    public static function showSupportTicket(int $id)
    {

        $user_id = Auth::id();

        if (SupportTicket::where('id', $id)->where('user_id', $user_id)->doesntExist()) {

            return response()->json([

                'status' => 'error',
                'message' => 'invalid record',

            ], 404);
        }

        $columns = [

            'support_tickets.id',
            'statuses.status_name',
            'support_tickets.created_at',
            'support_topics.id as support_topic_id',
            'support_topics.support_topic_name as topic',
            'support_tickets.ticket_text as issue',

        ];

        $supportTicket = SupportTicket::select($columns)

            ->leftJoin('support_topics', 'support_tickets.support_topic_id', '=', 'support_topics.id')
            ->leftJoin('statuses', 'support_tickets.status_id', '=', 'statuses.id')

            ->where('support_tickets.user_id', $user_id)

            ->where('support_tickets.id', $id)

            ->orderBy('support_tickets.created_at', 'desc')

            ->with('ticketResponses')

            ->first();

        return response()->json([

            'status' => 'success',
            'data' => $supportTicket,

        ], 200);

    }
}
