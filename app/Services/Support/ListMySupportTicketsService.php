<?php

namespace App\Services\Support;

use App\Models\Status;
use App\Models\SupportTicket;
use App\Models\TicketResponse;
use App\Utilities\Auth;
use Illuminate\Http\Request;

class ListMySupportTicketsService
{
    public static function listMySupportTickets(Request $request)
    {

        $status = $request->input('status');

        $user_id = Auth::id();

        // get the needed status ids

        $open = Status::getStatusId('open');

        $closed = Status::getStatusId('closed');

        // query according to desired status

        $select = [

            'support_tickets.id',
            'support_topics.support_topic_name as topic',
            'support_tickets.ticket_text as issue',
            'support_tickets.created_at',
        ];

        switch ($status) {

            case 'all':

                $tickets = SupportTicket::select($select)

                    ->leftJoin('support_topics', 'support_tickets.support_topic_id', '=', 'support_topics.id')

                    ->where('support_tickets.user_id', $user_id)

                    ->orderBy('support_tickets.created_at', 'desc')

                    ->paginate(10);

                // add response count to each ticket

                $tickets->map(function ($ticket) {

                    if (TicketResponse::where('support_ticket_id', $ticket->id)

                        ->exists()
                    ) {

                        $ticket->response_count = TicketResponse::where('support_ticket_id', $ticket->id)

                            ->count();
                    } else {

                        $ticket->response_count = 0;
                    }

                    return $ticket;
                });

                return response()->json([

                    'status' => 'success',
                    'tickets' => $tickets,

                ], 200);

            case 'open':

                $tickets = SupportTicket::select($select)

                    ->leftJoin('support_topics', 'support_tickets.support_topic_id', '=', 'support_topics.id')

                    ->where('support_tickets.user_id', $user_id)
                    ->where('support_tickets.status_id', $open)

                    ->orderBy('support_tickets.created_at', 'desc')

                    ->paginate(10);

                // add response count to each ticket

                $tickets->map(function ($ticket) {

                    if (TicketResponse::where('support_ticket_id', $ticket->id)

                        ->exists()
                    ) {

                        $ticket->response_count = TicketResponse::where('support_ticket_id', $ticket->id)

                            ->count();
                    } else {

                        $ticket->response_count = 0;
                    }

                    return $ticket;
                });

                return response()->json([

                    'status' => 'success',
                    'tickets' => $tickets,

                ], 200);

            case 'closed':

                $tickets = SupportTicket::select($select)

                    ->leftJoin('support_topics', 'support_tickets.support_topic_id', '=', 'support_topics.id')

                    ->where('support_tickets.user_id', $user_id)
                    ->where('support_tickets.status_id', $closed)

                    ->orderBy('support_tickets.created_at', 'desc')

                    ->paginate(10);

                // add response count to each ticket

                $tickets->map(function ($ticket) {

                    if (TicketResponse::where('support_ticket_id', $ticket->id)

                        ->exists()
                    ) {

                        $ticket->response_count = TicketResponse::where('support_ticket_id', $ticket->id)

                            ->count();
                    } else {

                        $ticket->response_count = 0;
                    }

                    return $ticket;
                });

                return response()->json([

                    'status' => 'success',
                    'tickets' => $tickets,

                ], 200);

            default:

                $tickets = SupportTicket::select($select)

                    ->leftJoin('support_topics', 'support_tickets.support_topic_id', '=', 'support_topics.id')

                    ->where('support_tickets.user_id', $user_id)

                    ->orderBy('support_tickets.created_at', 'desc')

                    ->paginate(10);

                // add response count to each ticket

                $tickets->map(function ($ticket) {

                    if (TicketResponse::where('support_ticket_id', $ticket->id)

                        ->exists()
                    ) {

                        $ticket->response_count = TicketResponse::where('support_ticket_id', $ticket->id)

                            ->count();
                    } else {

                        $ticket->response_count = 0;
                    }

                    return $ticket;
                });

                return response()->json([

                    'status' => 'success',
                    'tickets' => $tickets,

                ], 200);
        }
    }
}
