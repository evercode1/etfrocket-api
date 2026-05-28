<?php

namespace App\Services\Support;

use App\Models\TicketResponse;
use App\Rules\TicketBelongsToUser;
use App\Rules\TicketIsOpen;
use App\Utilities\Auth;
use Illuminate\Http\Request;

class RespondToSupportService
{
    public static function respondToSupport(Request $request)
    {

        $user_id = Auth::id();

        $request->validate([

            'support_topic_id' => 'integer|required',
            'support_ticket_id' => ['integer', 'required', new TicketBelongsToUser($user_id), new TicketIsOpen],
            'response_text' => 'string|required|max:1000',

        ]);

        $ticketResponse = TicketResponse::create([

            'support_topic_id' => $request->support_topic_id,
            'support_ticket_id' => $request->support_ticket_id,
            'user_id' => $user_id,
            'response_text' => $request->response_text,
            'is_from_customer' => 1,
            'is_read' => 1,

        ]);

        return response()->json([

            'status' => 'success',
            'message' => 'Response added successfully.',
            'ticket_response' => $ticketResponse,

        ], 200);

    }
}
