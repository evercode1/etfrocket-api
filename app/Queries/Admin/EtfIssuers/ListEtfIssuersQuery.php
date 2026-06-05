<?php

namespace App\Queries\Admin\EtfIssuers;

use App\Models\EtfIssuer;
use App\Models\Status;
use Illuminate\Http\Request;

class ListEtfIssuersQuery
{
    public function getData(Request $request): array
    {
        $query =
            EtfIssuer::query()
                ->with([
                    'status:id,status_name',
                ]);

        if ($request->filled('search')) {

            $search =
                $request->input('search');

            $query->where(function ($query) use ($search) {

                $query

                    ->where(
                        'etf_issuer_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'website_url',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if ($request->filled('status_id')) {

            $query->where(
                'status_id',
                $request->input('status_id')
            );
        }

        $paginator =

            $query

                ->orderBy(
                    'etf_issuer_name'
                )

                ->paginate(
                    $request->input(
                        'per_page',
                        25
                    )
                )

                ->through(function (
                    EtfIssuer $issuer
                ) {

                    return [

                        'id' => $issuer->id,

                        'etf_issuer_name' => $issuer->etf_issuer_name,

                        'website_url' => $issuer->website_url,

                        'status' => $issuer
                            ->status
                            ?->status_name,

                        'updated_at' => $issuer->updated_at,

                    ];
                });

        return [

            'data' => $paginator,

            'meta' => [

                'total_active' => EtfIssuer::where(

                    'status_id',

                    Status::ACTIVE

                )->count(),

                'total_retired' => EtfIssuer::where(

                    'status_id',

                    Status::RETIRED

                )->count(),

            ],

        ];
    }
}
