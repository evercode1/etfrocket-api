<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\EtfIssuer;
use App\Models\Security;
use App\Services\Scrapers\RexSharesScraperService;
use App\Services\Scrapers\RoundhillScraperService;
use App\Services\Scrapers\YieldMaxScraperService;

class AiSecurityFundDataExtractionService
{
    public function __construct(
        private YieldMaxScraperService $yieldMaxScraperService,
        private RoundhillScraperService $roundhillScraperService,
        private RexSharesScraperService $rexSharesScraperService,
    ) {}

    public function extract(
        Security $security
    ): AiDataExtraction {

        $securityDetail =
            $security->detail;

        if (! $securityDetail) {

            throw new \RuntimeException(
                'Security detail record not found.'
            );
        }

        $extractedData =

            match (

                $securityDetail->etf_issuer_id

            ) {

                EtfIssuer::YIELDMAX => $this->yieldMaxScraperService
                    ->extract(
                        $security
                    ),
                EtfIssuer::ROUNDHILL => $this->roundhillScraperService
                    ->extract(

                        $security

                    ),
                EtfIssuer::REX => $this->rexSharesScraperService
                    ->extract(

                        $security

                    ),

                default => throw new \RuntimeException(

                    'No fund data scraper configured for ETF issuer ID: '.

                    $securityDetail->etf_issuer_id

                ),

            };

        return AiDataExtraction::create([

            'security_id' => $security->id,

            'data_source_id' => DataSource::WEB_SCRAPER,

            'source_url' => 'Fund data scraper for '.
                $security->symbol,

            'raw_payload' => json_encode(

                $extractedData

            ),

            'prompt' => 'Fund data scraper extraction for '.
                $security->symbol,

            'extracted_data' => $extractedData,

            'is_validated' => false,

            'validation_notes' => null,

            'processed_at' => now(),

        ]);
    }
}
