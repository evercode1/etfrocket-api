<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityNavHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessAiSecurityFundDataExtractionService
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
                            'Security not found for fund data extraction.'
                        );
                    }

                    $data =

                        $extraction->extracted_data;

                    if (! is_array($data)) {

                        throw new \RuntimeException(
                            'Extracted fund data is missing or invalid.'
                        );
                    }

                    $this->validateSymbol(
                        $security,
                        $data
                    );

                    $this->processFundData(
                        $extraction,
                        $data
                    );

                    $extraction->update([

                        'is_validated' => true,

                        'processed_at' => now(),

                        'failed_at' => null,

                        'failure_reason' => null,

                        'validation_notes' => 'Fund data extraction processed successfully.',

                    ]);

                    return $extraction->fresh();
                }

            );
        } catch (\Throwable $e) {

            $extraction->update([

                'is_validated' => false,

                'failed_at' => now(),

                'failure_reason' => $e->getMessage(),

                'validation_notes' => 'Fund data extraction failed processing.',

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

            strtoupper($data['symbol']) !==
            strtoupper($security->symbol)

        ) {

            throw new \RuntimeException(
                'Extracted symbol does not match Security symbol.'
            );
        }
    }

    private function processFundData(
        AiDataExtraction $extraction,
        array $data
    ): void {

        if (

            empty($data['assets_under_management'])

            &&

            empty($data['nav_per_share'])

        ) {

            throw new \RuntimeException(
                'No fund data was extracted.'
            );
        }

        $this->processAum(
            $extraction,
            $data
        );

        $this->processNav(
            $extraction,
            $data
        );
    }

    private function processAum(
        AiDataExtraction $extraction,
        array $data
    ): void {

        if (

            empty($data['assets_under_management'])

            ||

            empty($data['aum_date'])

        ) {

            return;
        }

        $aum =

            $this->positiveInteger(

                $data['assets_under_management'],

                'assets_under_management'

            );

        $aumDate =

            $this->freshDate(

                $data['aum_date'],

                'aum_date'

            );

        SecurityAumHistory::updateOrCreate(

            [

                'security_id' => $extraction->security_id,

                'aum_date' => $aumDate,

            ],

            [

                'assets_under_management' => $aum,

                'data_source_id' => $extraction->data_source_id,

                'retrieved_at' => now(),

            ]

        );
    }

    private function processNav(
        AiDataExtraction $extraction,
        array $data
    ): void {

        if (

            empty($data['nav_per_share'])

            ||

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

        SecurityNavHistory::updateOrCreate(

            [

                'security_id' => $extraction->security_id,

                'nav_date' => $navDate,

            ],

            [

                'nav_per_share' => $navPerShare,

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
                    ->subDays(14)
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
