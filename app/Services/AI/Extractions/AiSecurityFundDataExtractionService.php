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
    /*
    |--------------------------------------------------------------------------
    | Issuer -> scraper map
    |--------------------------------------------------------------------------
    |
    | Single source of truth for which ETF issuers have a fund data scraper.
    | Also used to decide public coverage (FundDataCoverage) and which
    | schedules the daily runner dispatches.
    |
    */

    public const SCRAPERS = [
        EtfIssuer::YIELDMAX => YieldMaxScraperService::class,
        EtfIssuer::ROUNDHILL => RoundhillScraperService::class,
        EtfIssuer::REX => RexSharesScraperService::class,
        EtfIssuer::GLOBAL_X => GlobalXScraperService::class,
        EtfIssuer::NEOS => NeosScraperService::class,
        EtfIssuer::TAPPALPHA => TappAlphaScraperService::class,
        EtfIssuer::NICHOLASX => NicholasXScraperService::class,
        EtfIssuer::KURV => KurvScraperService::class,
    ];

    /**
     * @return int[]
     */
    public static function supportedIssuerIds(): array
    {
        return array_keys(self::SCRAPERS);
    }

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

        $scraperClass =
            self::SCRAPERS[$securityDetail->etf_issuer_id] ?? null;

        if (! $scraperClass) {

            throw new \RuntimeException(

                'No fund data scraper configured for ETF issuer ID: '.

                $securityDetail->etf_issuer_id

            );
        }

        $extractedData =
            app($scraperClass)
                ->extract(
                    $security
                );

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
