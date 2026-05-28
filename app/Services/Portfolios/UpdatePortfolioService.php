<?php

namespace App\Services\Portfolios;

use App\Models\Portfolio;
use Illuminate\Support\Facades\DB;

class UpdatePortfolioService
{
    public function update(int $userId, int $portfolioId, array $data): Portfolio
    {
        return DB::transaction(function () use ($userId, $portfolioId, $data) {

            $portfolio = Portfolio::where('user_id', $userId)
                ->where('id', $portfolioId)
                ->firstOrFail();

            if (array_key_exists('portfolio_name', $data)) {
                $portfolio->portfolio_name = $data['portfolio_name'];
            }

            if (array_key_exists('is_default', $data)) {

                $isDefault = (bool) $data['is_default'];

                if ($isDefault) {
                    Portfolio::where('user_id', $userId)
                        ->where('id', '!=', $portfolio->id)
                        ->update(['is_default' => false]);
                }

                $portfolio->is_default = $isDefault;
            }

            $portfolio->save();

            return $portfolio->refresh();
        });
    }
}
