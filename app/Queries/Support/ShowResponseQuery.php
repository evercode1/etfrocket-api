<?php

namespace App\Queries\Support;

use App\Models\TicketResponse;
use Illuminate\Http\Request;

class ShowResponseQuery
{
    public static function showResponse(Request $request)
    {

        $ticket_response_id = $request->input('ticket_response_id');

        $response = TicketResponse::leftJoin('support_topics', 'ticket_responses.support_topic_id', '=', 'support_topics.id')

            ->leftJoin('support_tickets', 'ticket_responses.support_ticket_id', '=', 'support_tickets.id')

            ->where('ticket_responses.id', $ticket_response_id)

            ->orderBy('support_tickets.id', 'asc')

            ->first();

        $response->is_read = 1;

        $response->save();

        return response()->json([

            'status' => 'success',
            'message' => 'Response retrieved successfully.',
            'ticket_response' => $response,

        ], 200);

    }
}
