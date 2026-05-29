<?php

namespace Tests\Unit\AiExtraction;

use App\Models\AiDataExtraction;
use App\Models\DataSource;
use App\Models\Security;
use App\Services\AI\Extractions\ProcessAiSecurityNavExtractionService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessAiSecurityNavExtractionServiceTest extends TestCase
{
    private ProcessAiSecurityNavExtractionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_nav_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        $this->service = app(

            ProcessAiSecurityNavExtractionService::class
        );
    }

    protected function tearDown(): void
    {
        DB::table('security_nav_histories')->truncate();

        DB::table('ai_data_extractions')->truncate();

        DB::table('securities')->truncate();

        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_processes_nav_extraction()
    {

        Security::factory()
            ->create([

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

                        'nav_per_share' => 25.55,

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
            'security_nav_histories',
            [

                'nav_per_share' => 25.55,

            ]
        );
    }

    public function test_it_fails_if_symbol_is_missing()
    {

        Security::factory()
            ->create([

                'symbol' => 'CHPY',
            ]);
        $security =
            Security::firstOrFail();

        $extraction =
            AiDataExtraction::factory()
                ->create([

                    'security_id' => $security->id,

                    'extracted_data' => [

                        'nav_per_share' => 25.55,

                        'nav_date' => now()->toDateString(),

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
