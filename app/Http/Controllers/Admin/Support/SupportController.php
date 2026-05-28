<?php

namespace App\Http\Controllers\Admin\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportReplyToTicketRequest;
use App\Models\Status;
use App\Models\SupportTicket;
use App\Queries\Admin\Support\ShowTicketQuery;
use App\Services\Support\CloseTicketService;
use App\Services\Support\FiltersForSupportService;
use App\Services\Support\ReplyFormConfigService;
use App\Services\Support\SupportReplyToTicketService;
use App\Services\Support\SupportTableDataService;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request, SupportTableDataService $service)
    {
        $request->validate([

            'status' => 'required|integer',

        ]);

        return $service->getTickets($request);
    }

    public function show(int $id)
    {

        return ShowTicketQuery::getTicket($id);
    }

    public function getSupportDataFilters()
    {

        return FiltersForSupportService::getFilters();
    }

    public function getSupportReplyFormConfig()
    {

        return ReplyFormConfigService::getReplyFormConfig();
    }

    public function supportReplyToTicket(SupportReplyToTicketRequest $request)
    {

        return SupportReplyToTicketService::storeSupportReplyToTicket($request);
    }

    public function closeTicket(Request $request)
    {

        return CloseTicketService::closeTicket($request);
    }

    public function openTicketCount()
    {

        $open_support_ticket_count = SupportTicket::where('status_id', Status::OPEN)->count();

        return response()->json([
            'status' => 'success',
            'open_support_ticket_count' => $open_support_ticket_count,
        ], 200);
    }
}
