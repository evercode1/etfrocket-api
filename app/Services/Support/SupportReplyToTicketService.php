<?php

namespace App\Services\Support;

use App\Models\Status;
use App\Models\SupportTicket;
use App\Models\TicketResponse;
use Illuminate\Support\Facades\DB;

class SupportReplyToTicketService
{
    public static function storeSupportReplyToTicket($request)
    {

        // Start transaction!

        DB::beginTransaction();

        try {

            // update original ticket as closed if needed

            if ($request->get('status') == 'close') {

                $support_ticket_id = $request->get('support_ticket_id');

                $originalTicket = SupportTicket::find($support_ticket_id);

                $originalTicket->status_id = Status::getStatusId('closed');

                $originalTicket->save();

            }

            // create response

            $response = TicketResponse::create([

                'support_topic_id' => $request->support_topic_id,
                'support_ticket_id' => $request->support_ticket_id,
                'user_id' => $request->user_id,
                'is_from_customer' => 0,
                'response_text' => $request->response_text,
                'is_read' => 0,

            ]);

        } catch (\Exception $e) {

            DB::rollback();
            throw $e;
        }

        DB::commit();

        return response()->json([

            'status' => 'success',
            'message' => 'Response added to ticket successfully',
            'response' => $response,

        ], 200);

    }
}
