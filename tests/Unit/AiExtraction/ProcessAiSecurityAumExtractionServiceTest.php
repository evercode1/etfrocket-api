<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Security;
use App\Services\AI\Extractions\ProcessAiSecurityAumExtractionService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessAiSecurityAumExtractionServiceTest extends TestCase
{
    private ProcessAiSecurityAumExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_aum_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        $this->service =
            app(
                ProcessAiSecurityAumExtractionService::class
            );
    }

    protected function tearDown(): void
    {
        DB::table('security_aum_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_processes_aum_extraction()
    {

        Security::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $security =
            Security::firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'data_source_id' => DataSource::MANUAL_ENTRY,

                    'extracted_data' => [

                        'symbol' => $security->symbol,

                        'assets_under_management' => 1000000000,

                        'aum_date' => now()->toDateString(),

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

                'assets_under_management' => 1000000000,

            ]
        );
    }

    public function test_it_fails_if_aum_date_is_stale()
    {

        Security::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $security =
            Security::firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'extracted_data' => [

                        'symbol' => $security->symbol,

                        'assets_under_management' => 1000000000,

                        'aum_date' => now()
                            ->subDays(30)
                            ->toDateString(),

                    ],

                ]);

        $this->expectException(
            \RuntimeException::class
        );

        $this->service
            ->process(
                $extraction
            );
    }
}
