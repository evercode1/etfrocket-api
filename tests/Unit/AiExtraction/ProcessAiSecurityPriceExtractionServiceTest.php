<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Security;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Services\AI\Extractions\ProcessAiSecurityPriceExtractionService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessAiSecurityPriceExtractionServiceTest extends TestCase
{
    private ProcessAiSecurityPriceExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_price_histories')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        $this->service =
            app(
                ProcessAiSecurityPriceExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_price_histories')
            ->truncate();

        DB::table('ai_data_extractions')
            ->truncate();

        DB::table('securities')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_processes_price_extraction()
    {

        Security::create([
            'symbol' => 'CHPY',
            'status_id' => Status::ACTIVE,
        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'data_source_id' => DataSource::MANUAL_ENTRY,

                    'extracted_data' => [

                        'symbol' => 'CHPY',

                        'close_price' => 25.44,

                        'price_date' => now()->toDateString(),

                        'volume' => 250000,

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
            'security_price_histories',
            [

                'security_id' => $security->id,

                'close_price' => 25.44,

                'volume' => 250000,

            ]
        );
    }

    public function test_it_updates_existing_price_record()
    {

        Security::create([
            'symbol' => 'CHPY',
            'status_id' => Status::ACTIVE,
        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        SecurityPriceHistory::create([

            'security_id' => $security->id,

            'price_date' => now()->toDateString(),

            'close_price' => 10.00,

            'volume' => 1000,

            'data_source_id' => DataSource::MANUAL_ENTRY,

            'retrieved_at' => now(),

        ]);

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'data_source_id' => DataSource::MANUAL_ENTRY,

                    'extracted_data' => [

                        'symbol' => 'CHPY',

                        'close_price' => 44.55,

                        'price_date' => now()->toDateString(),

                        'volume' => 999999,

                    ],

                ]);

        $this->service
            ->process(
                $extraction
            );

        $this->assertDatabaseCount(
            'security_price_histories',
            1
        );

        $this->assertDatabaseHas(
            'security_price_histories',
            [

                'close_price' => 44.55,

                'volume' => 999999,

            ]
        );
    }

    public function test_it_fails_if_symbol_is_missing()
    {

        Security::create([
            'symbol' => 'CHPY',
            'status_id' => Status::ACTIVE,
        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'extracted_data' => [

                        'close_price' => 10.22,

                        'price_date' => now()->toDateString(),

                        'volume' => 1000,

                    ],

                ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Extracted symbol is missing.'
        );

        $this->service
            ->process(
                $extraction
            );
    }

    public function test_it_fails_if_symbol_does_not_match()
    {

        Security::create([
            'symbol' => 'CHPY',
            'status_id' => Status::ACTIVE,
        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'extracted_data' => [

                        'symbol' => 'WRONG',

                        'close_price' => 10.22,

                        'price_date' => now()->toDateString(),

                        'volume' => 1000,

                    ],

                ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Extracted symbol does not match security symbol.'
        );

        $this->service
            ->process(
                $extraction
            );
    }

    public function test_it_fails_if_price_date_is_stale()
    {

        Security::create([
            'symbol' => 'CHPY',
            'status_id' => Status::ACTIVE,
        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'extracted_data' => [

                        'symbol' => 'CHPY',

                        'close_price' => 12.55,

                        'price_date' => now()
                            ->subDays(10)
                            ->toDateString(),

                        'volume' => 1000,

                    ],

                ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'price_date is stale.'
        );

        $this->service
            ->process(
                $extraction
            );
    }

    public function test_it_marks_extraction_as_failed()
    {

        Security::create([
            'symbol' => 'CHPY',
            'status_id' => Status::ACTIVE,
        ]);

        $security =
            Security::where(
                'symbol',
                'CHPY'
            )->firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'extracted_data' => [

                        'symbol' => 'WRONG',

                        'close_price' => 12.55,

                        'price_date' => now()->toDateString(),

                        'volume' => 1000,

                    ],

                ]);

        try {

            $this->service
                ->process(
                    $extraction
                );
        } catch (\Throwable $e) {

            //
        }

        $extraction->refresh();

        $this->assertFalse(
            $extraction->is_validated
        );

        $this->assertNotNull(
            $extraction->failed_at
        );

        $this->assertNotNull(
            $extraction->failure_reason
        );
    }
}
