<?php

namespace App\Services\Portfolios;

use App\Models\Portfolio;
use App\Models\Status;
use Illuminate\Support\Facades\DB;

class CreatePortfolioService
{
    public function create(int $userId, array $data): Portfolio
    {
        return DB::transaction(function () use ($userId, $data) {

            /*
            |--------------------------------------------------------------------------
            | Normalize default flag
            |--------------------------------------------------------------------------
            */

            $isDefaultRequested = filter_var(
                $data['is_default'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            );

            /*
            |--------------------------------------------------------------------------
            | First portfolio is automatically default
            |--------------------------------------------------------------------------
            */

            $userHasPortfolios = Portfolio::where('user_id', $userId)
                ->exists();

            $isDefault = ! $userHasPortfolios || $isDefaultRequested;

            /*
            |--------------------------------------------------------------------------
            | Clear previous defaults if new portfolio is default
            |--------------------------------------------------------------------------
            */

            if ($isDefault) {

                Portfolio::where('user_id', $userId)
                    ->update([
                        'is_default' => 0,
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Create portfolio
            |--------------------------------------------------------------------------
            */

            return Portfolio::create([

                'user_id' => $userId,

                'status_id' => Status::ACTIVE,

                'portfolio_name' => $data['portfolio_name'],

                'is_default' => $isDefault ? 1 : 0,

            ]);
        });
    }
}
