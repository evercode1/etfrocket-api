<?php

namespace Tests\Unit\Queries\Securities;

use App\Models\DistributionFrequency;
use App\Models\EtfIssuer;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityType;
use App\Models\Status;
use App\Queries\Securities\SecuritySummaryQuery;
use Database\Seeders\DistributionFrequencySeeder;
use Database\Seeders\EtfIssuerSeeder;
use Database\Seeders\SecurityTypeSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecuritySummaryQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::table('security_details')->truncate();
        DB::table('securities')->truncate();
        DB::table('etf_issuers')->truncate();
        DB::table('distribution_frequencies')->truncate();
        DB::table('security_types')->truncate();

        $this->seed([

            EtfIssuerSeeder::class,
            DistributionFrequencySeeder::class,
            SecurityTypeSeeder::class,

        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();

        DB::table('security_details')->truncate();
        DB::table('securities')->truncate();
        DB::table('etf_issuers')->truncate();
        DB::table('distribution_frequencies')->truncate();
        DB::table('security_types')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_security_summary_data(): void
    {
        $security = Security::create([
            'symbol' => 'ABNY',
            'security_type_id' => SecurityType::ETF,
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'security_name' => 'YieldMax ABNB Option Income Strategy ETF',
            'etf_issuer_id' => EtfIssuer::YIELDMAX,
            'distribution_frequency_id' => DistributionFrequency::WEEKLY,
            'expense_ratio' => 0.99,
            'sector' => 'Technology',
            'website_url' => 'https://yieldmaxetfs.com',
        ]);

        $result = app(
            SecuritySummaryQuery::class
        )->getData('ABNY');

        $this->assertSame(
            $security->id,
            $result['id']
        );

        $this->assertSame(
            'ABNY',
            $result['symbol']
        );

        $this->assertSame(
            'YieldMax ABNB Option Income Strategy ETF',
            $result['security_name']
        );

        $this->assertSame(
            'ETF',
            $result['security_type_name']
        );

        $this->assertSame(
            'YieldMax',
            $result['issuer_name']
        );

        $this->assertSame(
            'Weekly',
            $result['distribution_frequency_name']
        );

        $this->assertSame(
            '0.9900',
            (string) $result['expense_ratio']
        );

        $this->assertSame(
            'Technology',
            $result['sector']
        );

        $this->assertSame(
            'https://yieldmaxetfs.com',
            $result['website_url']
        );

        $this->assertSame(
            'https://finance.yahoo.com/quote/ABNY/',
            $result['yahoo_finance_url']
        );
    }

    public function test_it_throws_exception_when_symbol_does_not_exist(): void
    {
        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            SecuritySummaryQuery::class
        )->getData('DOESNOTEXIST');
    }
}
