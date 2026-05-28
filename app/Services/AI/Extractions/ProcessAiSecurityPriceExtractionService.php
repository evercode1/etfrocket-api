<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\Security;
use App\Models\SecurityPriceHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessAiSecurityPriceExtractionService
{
    public function process(
        AiDataExtraction $extraction
    ): AiDataExtraction {

        try {

            return DB::transaction(

                function () use ($extraction) {

                    $security =

                        Security::find(
                            $extraction->security_id
                        );

                    if (! $security) {

                        throw new \RuntimeException(
                            'Security not found for AI price extraction.'
                        );
                    }

                    $data =

                        $extraction->extracted_data;

                    if (! is_array($data)) {

                        throw new \RuntimeException(
                            'Extracted security price data is missing or invalid.'
                        );
                    }

                    $this->validateSymbol(
                        $security,
                        $data
                    );

                    $this->processPrice(
                        $extraction,
                        $data
                    );

                    $extraction->update([

                        'is_validated' => true,

                        'processed_at' => now(),

                        'failed_at' => null,

                        'failure_reason' => null,

                        'validation_notes' => 'AI ETF price extraction processed successfully.',

                    ]);

                    return $extraction->fresh();
                }

            );
        } catch (\Throwable $e) {

            $extraction->update([

                'is_validated' => false,

                'failed_at' => now(),

                'failure_reason' => $e->getMessage(),

                'validation_notes' => 'AI ETF price extraction failed processing.',

            ]);

            throw $e;
        }
    }

    private function validateSymbol(
        Security $security,
        array $data
    ): void {

        if (! isset($data['symbol'])) {

            throw new \RuntimeException(
                'Extracted symbol is missing.'
            );
        }

        if (

            strtoupper(
                $data['symbol']
            ) !==

            strtoupper(
                $security->symbol
            )

        ) {

            throw new \RuntimeException(
                'Extracted symbol does not match security symbol.'
            );
        }
    }

    private function processPrice(
        AiDataExtraction $extraction,
        array $data
    ): void {

        if (

            empty($data['close_price']) ||

            empty($data['price_date'])

        ) {

            return;
        }

        $closePrice =

            $this->positiveNumber(

                $data['close_price'],

                'close_price'

            );

        $priceDate =

            $this->freshDate(

                $data['price_date'],

                'price_date'

            );

        $volume = null;

        if (

            isset($data['volume']) &&

            $data['volume'] !== null

        ) {

            $volume =

                $this->positiveInteger(

                    $data['volume'],

                    'volume'

                );
        }

        SecurityPriceHistory::updateOrCreate(

            [

                'security_id' => $extraction->security_id,

                'price_date' => $priceDate,

            ],

            [

                'close_price' => $closePrice,

                'volume' => $volume,

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

    private function positiveInteger(
        mixed $value,
        string $field
    ): int {

        if (! is_numeric($value)) {

            throw new \RuntimeException(
                $field.' must be numeric.'
            );
        }

        $value =

            (int) $value;

        if ($value <= 0) {

            throw new \RuntimeException(
                $field.' must be greater than zero.'
            );
        }

        return $value;
    }

    private function freshDate(
        mixed $value,
        string $field
    ): string {

        if (! is_string($value)) {

            throw new \RuntimeException(
                $field.' must be a valid date string.'
            );
        }

        try {

            $date =

                Carbon::parse(
                    $value
                )->startOfDay();
        } catch (\Throwable $e) {

            throw new \RuntimeException(
                $field.' must be a valid date.'
            );
        }

        if (

            $date->lt(

                now()

                    ->subDays(3)

                    ->startOfDay()

            )

        ) {

            throw new \RuntimeException(
                $field.' is stale.'
            );
        }

        return $date->toDateString();
    }
}
