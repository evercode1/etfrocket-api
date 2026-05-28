<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Models\EtfDividendHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessAiEtfDividendExtractionService
{
    public function process(
        AiDataExtraction $extraction
    ): AiDataExtraction {

        try {

            return DB::transaction(

                function () use ($extraction) {

                    $etf =

                        Etf::find(
                            $extraction->etf_id
                        );

                    if (! $etf) {

                        throw new \RuntimeException(
                            'ETF not found for AI dividend extraction.'
                        );
                    }

                    $data =

                        $extraction->extracted_data;

                    if (! is_array($data)) {

                        throw new \RuntimeException(
                            'Extracted ETF dividend data is missing or invalid.'
                        );
                    }

                    $this->validateSymbol(
                        $etf,
                        $data
                    );

                    $this->processDividend(
                        $extraction,
                        $data
                    );

                    $extraction->update([

                        'is_validated' => true,

                        'processed_at' => now(),

                        'failed_at' => null,

                        'failure_reason' => null,

                        'validation_notes' => 'AI ETF dividend extraction processed successfully.',

                    ]);

                    return $extraction->fresh();
                }

            );
        } catch (\Throwable $e) {

            $extraction->update([

                'is_validated' => false,

                'failed_at' => now(),

                'failure_reason' => $e->getMessage(),

                'validation_notes' => 'AI ETF dividend extraction failed processing.',

            ]);

            throw $e;
        }
    }

    private function validateSymbol(
        Etf $etf,
        array $data
    ): void {

        if (! isset($data['symbol'])) {

            throw new \RuntimeException(
                'Extracted symbol is missing.'
            );
        }

        if (

            strtoupper($data['symbol']) !==
            strtoupper($etf->symbol)

        ) {

            throw new \RuntimeException(
                'Extracted symbol does not match ETF symbol.'
            );
        }
    }

    private function processDividend(
        AiDataExtraction $extraction,
        array $data
    ): void {

        if (

            empty($data['dividend_amount']) ||

            empty($data['ex_dividend_date'])

        ) {

            return;
        }

        $dividendAmount =

            $this->positiveNumber(

                $data['dividend_amount'],

                'dividend_amount'

            );

        $exDividendDate =

            $this->validDate(

                $data['ex_dividend_date'],

                'ex_dividend_date'

            );

        $paymentDate = null;

        if (

            ! empty($data['payment_date'])

        ) {

            $paymentDate =

                $this->validDate(

                    $data['payment_date'],

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

    private function positiveNumber(
        mixed $value,
        string $field
    ): float {

        if (! is_numeric($value)) {

            throw new \RuntimeException(
                $field.' must be numeric.'
            );
        }

        $value =
            (float) $value;

        if ($value <= 0) {

            throw new \RuntimeException(
                $field.' must be greater than zero.'
            );
        }

        return round(
            $value,
            4
        );
    }

    private function validDate(
        mixed $value,
        string $field
    ): string {

        if (! is_string($value)) {

            throw new \RuntimeException(
                $field.' must be a valid date string.'
            );
        }

        try {

            return Carbon::parse(
                $value
            )->toDateString();
        } catch (\Throwable $e) {

            throw new \RuntimeException(
                $field.' must be a valid date.'
            );
        }
    }
}
