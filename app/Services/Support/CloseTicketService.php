<?php

namespace App\Services\Support;

use App\Models\Status;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class CloseTicketService
{
    public static function closeTicket(Request $request)
    {

        $support_ticket_id = $request->input('id');

        $originalTicket = SupportTicket::find($support_ticket_id);

        $originalTicket->status_id = Status::getStatusId('closed');

        $originalTicket->save();

        // format json response

        return response()->json([

            'status' => 'success',
            'message' => 'You have closed ticket# '.$originalTicket->id,

        ], 200);

    }
}
