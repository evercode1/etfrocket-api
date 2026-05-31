<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityNavHistory;
use App\Models\Status;
use App\Services\AI\Extractions\ProcessAiSecurityFundDataExtractionService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessAiSecurityFundDataExtractionServiceTest extends TestCase
{
    private ProcessAiSecurityFundDataExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_aum_histories')
            ->truncate();

        DB::table('security_nav_histories')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();

        $this->service =
            app(
                ProcessAiSecurityFundDataExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_aum_histories')
            ->truncate();

        DB::table('security_nav_histories')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_processes_fund_data()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                    'status_id' => Status::ACTIVE,

                ]);

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'data_source_id' => DataSource::WEB_SCRAPER,

                    'extracted_data' => [

                        'symbol' => 'CHPY',

                        'assets_under_management' => 1020000000,

                        'aum_date' => now()->toDateString(),

                        'nav_per_share' => 80.80,

                        'nav_date' => now()->toDateString(),

                    ],

                ]);

        $result =
            $this->service
                ->process(
                    $extraction
                );

        $this->assertTrue(
            $result->is_validated
        );

        $this->assertDatabaseHas(
            'security_aum_histories',
            [

                'security_id' => $security->id,

                'assets_under_management' => 1020000000,

            ]
        );

        $this->assertDatabaseHas(
            'security_nav_histories',
            [

                'security_id' => $security->id,

                'nav_per_share' => 80.80,

            ]
        );
    }

    public function test_it_updates_existing_records()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                    'status_id' => Status::ACTIVE,

                ]);

        SecurityAumHistory::create([

            'security_id' => $security->id,

            'aum_date' => now()->toDateString(),

            'assets_under_management' => 100,

            'data_source_id' => DataSource::WEB_SCRAPER,

            'retrieved_at' => now(),

        ]);

        SecurityNavHistory::create([

            'security_id' => $security->id,

            'nav_date' => now()->toDateString(),

            'nav_per_share' => 1.00,

            'data_source_id' => DataSource::WEB_SCRAPER,

            'retrieved_at' => now(),

        ]);

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'data_source_id' => DataSource::WEB_SCRAPER,

                    'extracted_data' => [

                        'symbol' => 'CHPY',

                        'assets_under_management' => 1020000000,

                        'aum_date' => now()->toDateString(),

                        'nav_per_share' => 80.80,

                        'nav_date' => now()->toDateString(),

                    ],

                ]);

        $this->service
            ->process(
                $extraction
            );

        $this->assertDatabaseCount(
            'security_aum_histories',
            1
        );

        $this->assertDatabaseCount(
            'security_nav_histories',
            1
        );

        $this->assertDatabaseHas(
            'security_aum_histories',
            [

                'assets_under_management' => 1020000000,

            ]
        );

        $this->assertDatabaseHas(
            'security_nav_histories',
            [

                'nav_per_share' => 80.80,

            ]
        );
    }

    public function test_it_fails_if_symbol_does_not_match()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                    'status_id' => Status::ACTIVE,

                ]);

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'extracted_data' => [

                        'symbol' => 'WRONG',

                        'assets_under_management' => 1020000000,

                        'aum_date' => now()->toDateString(),

                        'nav_per_share' => 80.80,

                        'nav_date' => now()->toDateString(),

                    ],

                ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Extracted symbol does not match Security symbol.'
        );

        $this->service
            ->process(
                $extraction
            );
    }

    public function test_it_fails_when_no_fund_data_exists()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                    'status_id' => Status::ACTIVE,

                ]);

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'extracted_data' => [

                        'symbol' => 'CHPY',

                        'assets_under_management' => null,

                        'aum_date' => null,

                        'nav_per_share' => null,

                        'nav_date' => null,

                    ],

                ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'No fund data was extracted.'
        );

        $this->service
            ->process(
                $extraction
            );
    }

    public function test_it_fails_if_aum_date_is_stale()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                    'status_id' => Status::ACTIVE,

                ]);

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'extracted_data' => [

                        'symbol' => 'CHPY',

                        'assets_under_management' => 1020000000,

                        'aum_date' => now()
                            ->subDays(30)
                            ->toDateString(),

                        'nav_per_share' => 80.80,

                        'nav_date' => now()->toDateString(),

                    ],

                ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'aum_date is stale.'
        );

        $this->service
            ->process(
                $extraction
            );
    }

    public function test_it_fails_if_nav_date_is_stale()
    {
        $security =
            Security::factory()
                ->create([

                    'symbol' => 'CHPY',

                    'status_id' => Status::ACTIVE,

                ]);

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'extracted_data' => [

                        'symbol' => 'CHPY',

                        'assets_under_management' => 1020000000,

                        'aum_date' => now()->toDateString(),

                        'nav_per_share' => 80.80,

                        'nav_date' => now()
                            ->subDays(30)
                            ->toDateString(),

                    ],

                ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'nav_date is stale.'
        );

        $this->service
            ->process(
                $extraction
            );
    }
}
