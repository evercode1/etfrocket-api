<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Services\AI\Extractions\AiSecurityFundDataExtractionService;
use App\Services\Scrapers\RexSharesScraperService;
use App\Services\Scrapers\YieldMaxScraperService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiSecurityFundDataExtractionServiceTest extends TestCase
{
    private AiSecurityFundDataExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();

        $this->service =
            app(
                AiSecurityFundDataExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_creates_fund_data_extraction()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                ]);

        $securityDetail =
            SecurityDetail::where(
                'security_id',
                $security->id
            )->firstOrFail();

        $securityDetail->update([

            'etf_issuer_id' => EtfIssuer::YIELDMAX,

        ]);

        $scraper =

            $this->createMock(
                YieldMaxScraperService::class
            );

        $scraper
            ->expects($this->once())
            ->method('extract')
            ->willReturn([

                'symbol' => 'CHPY',

                'assets_under_management' => 1020000000,

                'aum_date' => '2026-05-29',

                'nav_per_share' => 80.80,

                'nav_date' => '2026-05-29',

                'shares_outstanding' => 12650000,

            ]);

        $this->app->instance(

            YieldMaxScraperService::class,

            $scraper

        );

        $service =

            app(
                AiSecurityFundDataExtractionService::class
            );

        $extraction =

            $service->extract(
                $security
            );

        $this->assertInstanceOf(

            AiDataExtraction::class,

            $extraction

        );

        $this->assertEquals(

            $security->id,

            $extraction->security_id

        );

        $this->assertEquals(

            'CHPY',

            $extraction
                ->extracted_data['symbol']

        );

        $this->assertEquals(

            1020000000,

            $extraction
                ->extracted_data['assets_under_management']

        );

        $this->assertDatabaseCount(

            'ai_data_extractions',

            1

        );
    }

    public function test_it_throws_exception_for_unknown_issuer()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                ]);

        $securityDetail =
            SecurityDetail::where(
                'security_id',
                $security->id
            )->firstOrFail();

        $securityDetail->update([

            'etf_issuer_id' => 999999,

        ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'No fund data scraper configured for ETF issuer ID: 999999'
        );

        $this->service
            ->extract(
                $security
            );
    }

    public function test_it_throws_exception_when_security_detail_is_missing()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                ]);

        SecurityDetail::where(
            'security_id',
            $security->id
        )->delete();

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Security detail record not found.'
        );

        $this->service
            ->extract(
                $security
            );
    }

    public function test_it_extracts_rex_fund_data(): void
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'NVII',

                ]);

        $security->detail->update([

            'etf_issuer_id' => EtfIssuer::REX,

        ]);

        $expectedData = [

            'symbol' => 'NVII',

            'assets_under_management' => 101193600,

            'aum_date' => '2026-05-28',

            'nav_per_share' => 26.57,

            'nav_date' => '2026-05-28',

            'shares_outstanding' => 3810000,

        ];

        $this->mock(
            RexSharesScraperService::class,
            function ($mock) use (
                $security,
                $expectedData
            ) {

                $mock->shouldReceive(
                    'extract'
                )
                    ->once()
                    ->with(
                        $security
                    )
                    ->andReturn(
                        $expectedData
                    );
            }
        );

        $extraction =

            app(
                AiSecurityFundDataExtractionService::class
            )->extract(
                $security
            );

        $this->assertInstanceOf(
            AiDataExtraction::class,
            $extraction
        );

        $this->assertEquals(
            $security->id,
            $extraction->security_id
        );

        $this->assertEquals(
            DataSource::WEB_SCRAPER,
            $extraction->data_source_id
        );

        $this->assertEquals(
            $expectedData,
            $extraction->extracted_data
        );

        $this->assertFalse(
            $extraction->is_validated
        );
    }
}
