<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Services\AI\Extractions\AiSecurityFundDataExtractionService;
use App\Services\Scrapers\GlobalXScraperService;
use App\Services\Scrapers\KurvScraperService;
use App\Services\Scrapers\NeosScraperService;
use App\Services\Scrapers\NicholasXScraperService;
use App\Services\Scrapers\RexSharesScraperService;
use App\Services\Scrapers\TappAlphaScraperService;
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

    public function test_it_extracts_global_x_fund_data(): void
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CLIP',

                ]);

        $security->detail->update([

            'etf_issuer_id' => EtfIssuer::GLOBAL_X,

        ]);

        $expectedData = [

            'symbol' => 'CLIP',

            'assets_under_management' => 2829947160.45,

            'aum_date' => '2026-05-29',

            'nav_per_share' => 100.35,

            'nav_date' => '2026-05-29',

            'shares_outstanding' => 28199931,

        ];

        $this->mock(
            GlobalXScraperService::class,
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

    public function test_it_extracts_neos_fund_data(): void
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'QQQI',

                ]);

        $security->detail->update([

            'etf_issuer_id' => EtfIssuer::NEOS,

        ]);

        $expectedData = [

            'symbol' => 'QQQI',

            'assets_under_management' => 12351859425.00,

            'aum_date' => '2026-05-29',

            'nav_per_share' => 57.21,

            'nav_date' => '2026-05-29',

            'shares_outstanding' => 215890000,

        ];

        $this->mock(
            NeosScraperService::class,
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

    public function test_it_extracts_tappalpha_fund_data(): void
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'TDAQ',

                ]);

        $security->detail->update([

            'etf_issuer_id' => EtfIssuer::TAPPALPHA,

        ]);

        $expectedData = [

            'symbol' => 'TDAQ',

            'assets_under_management' => 210763410,

            'aum_date' => '2026-05-29',

            'nav_per_share' => 28.87,

            'nav_date' => '2026-05-29',

            'shares_outstanding' => 7260000,

        ];

        $this->mock(
            TappAlphaScraperService::class,
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

    public function test_it_extracts_nicholasx_fund_data(): void
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'BLOX',

                ]);

        $security->detail->update([

            'etf_issuer_id' => EtfIssuer::NICHOLASX,

        ]);

        $expectedData = [

            'symbol' => 'BLOX',

            'assets_under_management' => 331300000,

            'aum_date' => '2026-05-29',

            'nav_per_share' => 17.65,

            'nav_date' => '2026-05-29',

            'shares_outstanding' => 18775000,

        ];

        $this->mock(
            NicholasXScraperService::class,
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

    public function test_it_extracts_kurv_fund_data(): void
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'KQQQ',

                ]);

        $security->detail->update([

            'etf_issuer_id' => EtfIssuer::KURV,

        ]);

        $expectedData = [

            'symbol' => 'KQQQ',

            'assets_under_management' => 126494016,

            'aum_date' => '2026-05-29',

            'nav_per_share' => 31.4662,

            'nav_date' => '2026-05-29',

            'shares_outstanding' => 4019352,

        ];

        $this->mock(
            KurvScraperService::class,
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
