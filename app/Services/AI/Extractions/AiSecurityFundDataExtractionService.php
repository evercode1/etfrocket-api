<?php

namespace App\Services\AI\Extractions;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\EtfIssuer;
use App\Models\Security;
use App\Services\Scrapers\GlobalXScraperService;
use App\Services\Scrapers\KurvScraperService;
use App\Services\Scrapers\NeosScraperService;
use App\Services\Scrapers\NicholasXScraperService;
use App\Services\Scrapers\RexSharesScraperService;
use App\Services\Scrapers\RoundhillScraperService;
use App\Services\Scrapers\TappAlphaScraperService;
use App\Services\Scrapers\YieldMaxScraperService;

class AiSecurityFundDataExtractionService
{
    public function __construct(
        private YieldMaxScraperService $yieldMaxScraperService,
        private RoundhillScraperService $roundhillScraperService,
        private RexSharesScraperService $rexSharesScraperService,
        private GlobalXScraperService $globalXScraperService,
        private NeosScraperService $neosScraperService,
        private TappAlphaScraperService $tappAlphaScraperService,
        private NicholasXScraperService $nicholasXScraperService,
        private KurvScraperService $kurvScraperService,
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

                EtfIssuer::GLOBAL_X => $this->globalXScraperService
                    ->extract(

                        $security

                    ),

                EtfIssuer::NEOS => $this->neosScraperService
                    ->extract(

                        $security

                    ),

                EtfIssuer::TAPPALPHA => $this->tappAlphaScraperService
                    ->extract(

                        $security

                    ),
                EtfIssuer::NICHOLASX => $this->nicholasXScraperService
                    ->extract(

                        $security

                    ),

                EtfIssuer::KURV => $this->kurvScraperService
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
