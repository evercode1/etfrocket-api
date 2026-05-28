<?php

namespace App\Services\Support;

use App\Models\Status;
use App\Models\SupportTicket;
use App\Utilities\Auth;
use Illuminate\Http\Request;

class StoreSupportTicketService
{
    public static function storeSupportTicket(Request $request)
    {

        $request->validate([

            'support_topic_id' => 'integer|required',
            'ticket_text' => 'string|required|max:1000',

        ]);

        $open = Status::getStatusId('open');

        $user_id = Auth::id();

        $supportTicket = SupportTicket::create(

            array_merge($request->all(), [

                'status_id' => $open,
                'user_id' => $user_id,

            ])

        );

        return response()->json([

            'status' => 'success',
            'message' => 'Support ticket created successfully.',
            'support_ticket' => $supportTicket,

        ], 200);

    }
}
