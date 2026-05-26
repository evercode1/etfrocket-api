<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Models\EtfNavHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessAiEtfNavExtractionService
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
                            'ETF not found for AI NAV extraction.'
                        );
                    }

                    $data =

                        $extraction->extracted_data;

                    if (! is_array($data)) {

                        throw new \RuntimeException(
                            'Extracted ETF NAV data is missing or invalid.'
                        );
                    }

                    $this->validateSymbol(
                        $etf,
                        $data
                    );

                    $this->processNav(
                        $extraction,
                        $data
                    );

                    $extraction->update([

                        'is_validated' => true,

                        'processed_at' => now(),

                        'failed_at' => null,

                        'failure_reason' => null,

                        'validation_notes' =>

                        'AI ETF NAV extraction processed successfully.',

                    ]);

                    return $extraction->fresh();
                }

            );
        } catch (\Throwable $e) {

            $extraction->update([

                'is_validated' => false,

                'failed_at' => now(),

                'failure_reason' =>
                $e->getMessage(),

                'validation_notes' =>

                'AI ETF NAV extraction failed processing.',

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

    private function processNav(
        AiDataExtraction $extraction,
        array $data
    ): void {

        if (

            empty($data['nav_per_share']) ||

            empty($data['nav_date'])

        ) {

            return;
        }

        $navPerShare =

            $this->positiveNumber(

                $data['nav_per_share'],

                'nav_per_share'

            );

        $navDate =

            $this->freshDate(

                $data['nav_date'],

                'nav_date'

            );

        EtfNavHistory::updateOrCreate(

            [

                'etf_id' =>
                $extraction->etf_id,

                'nav_date' =>
                $navDate,

            ],

            [

                'nav_per_share' =>
                $navPerShare,

                'data_source_id' =>
                $extraction->data_source_id,

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
                $field . ' must be numeric.'
            );
        }

        $value =
            (float) $value;

        if ($value <= 0) {

            throw new \RuntimeException(
                $field . ' must be greater than zero.'
            );
        }

        return round(
            $value,
            4
        );
    }

    private function freshDate(
        mixed $value,
        string $field
    ): string {

        if (! is_string($value)) {

            throw new \RuntimeException(
                $field . ' must be a valid date string.'
            );
        }

        try {

            $date =

                Carbon::parse(
                    $value
                )->startOfDay();
        } catch (\Throwable $e) {

            throw new \RuntimeException(
                $field . ' must be a valid date.'
            );
        }

        if (

            $date->lt(

                now()
                    ->subDays(14)
                    ->startOfDay()

            )

        ) {

            throw new \RuntimeException(
                $field . ' is stale.'
            );
        }

        return $date->toDateString();
    }
}
