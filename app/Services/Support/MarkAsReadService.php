<?php

namespace App\Services\Support;

use App\Models\TicketResponse;
use Illuminate\Http\Request;

class MarkAsReadService
{
    public static function markAsRead(Request $request)
    {

        $ticket_response_id = $request->input('ticket_response_id');

        $response = TicketResponse::find($ticket_response_id);

        $response->is_read = 1;

        $response->save();

        return response()->json([

            'status' => 'success',
            'message' => 'The ticket response has been marked as read',

        ], 200);

    }
}
