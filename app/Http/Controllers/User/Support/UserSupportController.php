<?php

namespace App\Http\Controllers\User\Support;

use App\Http\Controllers\Controller;
use App\Models\TicketResponse;
use App\Queries\Support\ShowResponseQuery;
use App\Queries\Support\ShowSupportTicketQuery;
use App\Rules\TicketResponseBelongsToUser;
use App\Services\Support\ListMySupportTicketsService;
use App\Services\Support\MarkAsReadService;
use App\Services\Support\NewResponseFormConfigService;
use App\Services\Support\NewTicketFormConfigService;
use App\Services\Support\RespondToSupportService;
use App\Services\Support\StoreSupportTicketService;
use App\Utilities\Auth;
use Illuminate\Http\Request;

class UserSupportController extends Controller
{
    public function index(Request $request)
    {

        $request->validate([

            'status' => 'required|string',

        ]);

        return ListMySupportTicketsService::listMySupportTickets($request);
    }

    public function newTicketFormConfig()
    {

        return NewTicketFormConfigService::getNewTicketFormConfig();
    }

    public function newResponseFormConfig()
    {

        return NewResponseFormConfigService::getNewResponseFormConfig();
    }

    public function store(Request $request)
    {

        return StoreSupportTicketService::storeSupportTicket($request);
    }

    public function show(int $id)
    {

        return ShowSupportTicketQuery::showSupportTicket($id);
    }

    public function respondToSupport(Request $request)
    {

        return RespondToSupportService::respondToSupport($request);
    }

    public function showResponse(Request $request)
    {

        $user_id = Auth::id();

        $request->validate([

            'ticket_response_id' => ['integer', 'required', new TicketResponseBelongsToUser($user_id)],

        ]);

        return ShowResponseQuery::showResponse($request);
    }

    public function markAsRead(Request $request)
    {

        $user_id = Auth::id();

        $request->validate([

            'ticket_response_id' => ['integer', 'required', new TicketResponseBelongsToUser($user_id)],

        ]);

        return MarkAsReadService::markAsRead($request);
    }

    public function unreadResponses()
    {
        $user_id = Auth::id();

        $baseQuery = TicketResponse::query()
            ->leftJoin(
                'support_tickets',
                'ticket_responses.support_ticket_id',
                '=',
                'support_tickets.id'
            )
            ->leftJoin(
                'support_topics',
                'support_tickets.support_topic_id',
                '=',
                'support_topics.id'
            )
            ->where('support_tickets.user_id', $user_id)
            ->where('ticket_responses.is_from_customer', 0)
            ->where('ticket_responses.is_read', 0);

        $count = (clone $baseQuery)->count();

        if ($count === 0) {
            return response()->json([
                'status' => 'success',
                'unread_support_responses_count' => 0,
                'tickets' => [],
            ], 200);
        }

        $tickets = $baseQuery
            ->select([
                'support_topics.support_topic_name as topic',
                'ticket_responses.id as latest_response_id',
                'ticket_responses.support_ticket_id as ticket_id',
                'ticket_responses.response_text as message_preview',
                'ticket_responses.created_at',
                'support_tickets.ticket_text as ticket_issue',
            ])
            ->orderBy('ticket_responses.created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'unread_support_responses_count' => $count,
            'tickets' => $tickets,
        ], 200);
    }
}
