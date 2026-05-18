<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Models\EtfAumHistory;
use App\Models\EtfDividendHistory;
use App\Models\EtfNavHistory;
use App\Models\EtfPriceHistory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessAiEtfDataExtractionService
{
    public function process(AiDataExtraction $extraction): AiDataExtraction
    {
        try {
            return DB::transaction(function () use ($extraction) {

                $etf = Etf::find($extraction->etf_id);

                if (! $etf) {
                    throw new \RuntimeException('ETF not found for AI extraction.');
                }

                $data = $extraction->extracted_data;

                if (! is_array($data)) {
                    throw new \RuntimeException('Extracted data is missing or invalid.');
                }

                $this->validateSymbol($etf, $data);

                $this->processPrice($extraction, $data);

                $this->processNav($extraction, $data);

                $this->processAum($extraction, $data);

                $this->processDividend($extraction, $data);

                $extraction->update([
                    'is_validated' => true,
                    'processed_at' => now(),
                    'failed_at' => null,
                    'failure_reason' => null,
                    'validation_notes' => 'AI extracted ETF data processed successfully.',
                ]);

                return $extraction->fresh();
            });
        } catch (\Throwable $e) {

            $extraction->update([
                'is_validated' => false,
                'failed_at' => now(),
                'failure_reason' => $e->getMessage(),
                'validation_notes' => 'AI extracted ETF data failed processing.',
            ]);

            throw $e;
        }
    }

    private function validateSymbol(Etf $etf, array $data): void
    {
        if (! isset($data['symbol'])) {
            throw new \RuntimeException('Extracted symbol is missing.');
        }

        if (strtoupper($data['symbol']) !== strtoupper($etf->symbol)) {
            throw new \RuntimeException('Extracted symbol does not match ETF symbol.');
        }
    }

    private function processPrice(AiDataExtraction $extraction, array $data): void
    {
        $price = $data['price'] ?? null;

        if (! is_array($price)) {
            return;
        }

        if (
            empty($price['close_price']) ||
            empty($price['price_date'])
        ) {
            return;
        }

        $closePrice = $this->positiveNumber($price['close_price'], 'close_price');

        $priceDate = $this->freshDate($price['price_date'], 'price_date');

        EtfPriceHistory::updateOrCreate(
            [
                'etf_id' => $extraction->etf_id,
                'price_date' => $priceDate,
            ],
            [
                'close_price' => $closePrice,
                'volume' => $price['volume'] ?? null,
                'data_source_id' => $extraction->data_source_id,
                'retrieved_at' => now(),
            ]
        );
    }

    private function processNav(AiDataExtraction $extraction, array $data): void
    {
        $nav = $data['nav'] ?? null;

        if (! is_array($nav)) {
            return;
        }

        if (
            empty($nav['nav_per_share']) ||
            empty($nav['as_of_date'])
        ) {
            return;
        }

        $navDate = $this->freshDate($nav['as_of_date'], 'nav_as_of_date');

        $navPerShare = $this->positiveNumber($nav['nav_per_share'], 'nav_per_share');


        EtfNavHistory::updateOrCreate(
            [
                'etf_id' => $extraction->etf_id,
                'nav_date' => $navDate,
            ],
            [
                'nav_per_share' => $navPerShare,
                'data_source_id' => $extraction->data_source_id,
                'retrieved_at' => now(),
            ]
        );
    }

    private function processAum(AiDataExtraction $extraction, array $data): void
    {
        $aum = $data['aum'] ?? null;

        if (! is_array($aum)) {
            return;
        }

        if (
            empty($aum['assets_under_management']) ||
            empty($aum['as_of_date'])
        ) {
            return;
        }

        $assetsUnderManagement = $this->positiveInteger(
            $aum['assets_under_management'],
            'assets_under_management'
        );

        $aumDate = $this->freshDate($aum['as_of_date'], 'aum_as_of_date');

        EtfAumHistory::updateOrCreate(
            [
                'etf_id' => $extraction->etf_id,
                'aum_date' => $aumDate,
            ],
            [
                'assets_under_management' => $assetsUnderManagement,
                'data_source_id' => $extraction->data_source_id,
                'retrieved_at' => now(),
            ]
        );
    }


    private function processDividend(AiDataExtraction $extraction, array $data): void
    {
        $dividend = $data['dividend'] ?? null;

        if (! is_array($dividend)) {
            return;
        }

        if (
            empty($dividend['dividend_amount']) ||
            empty($dividend['ex_dividend_date'])
        ) {
            return;
        }

        $dividendAmount = $this->positiveNumber(
            $dividend['dividend_amount'],
            'dividend_amount'
        );

        $exDividendDate = $this->validDate(
            $dividend['ex_dividend_date'],
            'ex_dividend_date'
        );

        $paymentDate = null;

        if (! empty($dividend['payment_date'])) {
            $paymentDate = $this->validDate(
                $dividend['payment_date'],
                'payment_date'
            );
        }

        EtfDividendHistory::updateOrCreate(
            [
                'etf_id' => $extraction->etf_id,
                'ex_dividend_date' => $exDividendDate,
                'dividend_amount' => $dividendAmount,
            ],
            [
                'payment_date' => $paymentDate,
                'data_source_id' => $extraction->data_source_id,
                'retrieved_at' => now(),
            ]
        );
    }

    private function positiveNumber(mixed $value, string $field): float
    {
        if (! is_numeric($value)) {
            throw new \RuntimeException($field . ' must be numeric.');
        }

        $value = (float) $value;

        if ($value <= 0) {
            throw new \RuntimeException($field . ' must be greater than zero.');
        }

        return round($value, 4);
    }

    private function positiveInteger(mixed $value, string $field): int
    {
        if (! is_numeric($value)) {
            throw new \RuntimeException($field . ' must be numeric.');
        }

        $value = (int) $value;

        if ($value <= 0) {
            throw new \RuntimeException($field . ' must be greater than zero.');
        }

        return $value;
    }

    private function freshDate(mixed $value, string $field): string
    {
        if (! is_string($value)) {
            throw new \RuntimeException($field . ' must be a valid date string.');
        }

        try {
            $date = Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            throw new \RuntimeException($field . ' must be a valid date.');
        }

        if ($date->lt(now()->subDays(3)->startOfDay())) {
            throw new \RuntimeException($field . ' is stale.');
        }

        return $date->toDateString();
    }

    private function validDate(mixed $value, string $field): string
    {
        if (! is_string($value)) {
            throw new \RuntimeException($field . ' must be a valid date string.');
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            throw new \RuntimeException($field . ' must be a valid date.');
        }
    }
}
