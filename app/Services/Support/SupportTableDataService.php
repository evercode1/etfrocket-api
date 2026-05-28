<?php

namespace App\Services\Support;

use App\Models\Status;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTableDataService
{
    public function getTickets(Request $request)
    {

        $status = $request->input('status');

        // get the needed status ids

        $open = Status::getStatusId('open');

        $closed = Status::getStatusId('closed');

        $select = [

            'support_tickets.id',
            'support_topics.support_topic_name',
            'statuses.status_name',
            'users.name',
            'support_tickets.ticket_text',

        ];

        // query according to desired status

        switch ($status) {

            case 1:

                $tickets = SupportTicket::select($select)

                    ->leftJoin('support_topics', 'support_tickets.support_topic_id', '=', 'support_topics.id')
                    ->leftJoin('users', 'support_tickets.user_id', '=', 'users.id')
                    ->leftJoin('statuses', 'support_tickets.status_id', '=', 'statuses.id')

                    ->orderBy('support_tickets.id', 'desc')

                    ->paginate(10);

                // add details endpoint to each ticket

                $tickets->map(function ($ticket) {

                    $ticket->ticket_text = substr($ticket->ticket_text, 0, 24);

                    $ticket->details_endpoint = "support-ticket/{$ticket->id}";

                    return $ticket;

                });

                return response()->json([

                    'status' => 'success',
                    'tickets' => $tickets,

                ], 200);

            case 2:

                $tickets = SupportTicket::select($select)

                    ->leftJoin('support_topics', 'support_tickets.support_topic_id', '=', 'support_topics.id')
                    ->leftJoin('users', 'support_tickets.user_id', '=', 'users.id')
                    ->leftJoin('statuses', 'support_tickets.status_id', '=', 'statuses.id')

                    ->where('support_tickets.status_id', $closed)

                    ->orderBy('support_tickets.id', 'asc')

                    ->paginate(10);

                // add details endpoint to each ticket

                $tickets->map(function ($ticket) {

                    $ticket->ticket_text = substr($ticket->ticket_text, 0, 24);

                    $ticket->details_endpoint = "support-ticket/{$ticket->id}";

                    return $ticket;

                });

                return response()->json([

                    'status' => 'success',
                    'tickets' => $tickets,

                ], 200);

            case 3:

                $tickets = SupportTicket::select($select)

                    ->leftJoin('support_topics', 'support_tickets.support_topic_id', '=', 'support_topics.id')
                    ->leftJoin('users', 'support_tickets.user_id', '=', 'users.id')
                    ->leftJoin('statuses', 'support_tickets.status_id', '=', 'statuses.id')

                    ->where('support_tickets.status_id', $open)

                    ->orderBy('support_tickets.id', 'desc')

                    ->paginate(10);

                // add details endpoint to each ticket

                $tickets->map(function ($ticket) {

                    $ticket->ticket_text = substr($ticket->ticket_text, 0, 24);

                    $ticket->details_endpoint = "support-ticket/{$ticket->id}";

                    return $ticket;

                });

                return response()->json([

                    'status' => 'success',
                    'tickets' => $tickets,

                ], 200);

            default:

                $tickets = SupportTicket::select($select)

                    ->leftJoin('support_topics', 'support_tickets.support_topic_id', '=', 'support_topics.id')
                    ->leftJoin('users', 'support_tickets.user_id', '=', 'users.id')
                    ->leftJoin('statuses', 'support_tickets.status_id', '=', 'statuses.id')

                    ->orderBy('support_tickets.id', 'desc')

                    ->paginate(10);

                // add details endpoint to each ticket

                $tickets->map(function ($ticket) {

                    $ticket->ticket_text = substr($ticket->ticket_text, 0, 24);

                    $ticket->details_endpoint = "support-ticket/{$ticket->id}";

                    return $ticket;

                });

                return response()->json([

                    'status' => 'success',
                    'tickets' => $tickets,

                ], 200);
        }

    }
}
