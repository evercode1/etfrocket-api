<?php

namespace Tests\Unit\Commands;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillDividendHistoryTest extends TestCase
{
    protected array $testFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();

        $directory = app_path('Imports/DividendData');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->testFiles as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_backfills_dividend_history_from_csv_file(): void
    {
        $etf = Etf::factory()->create([
            'symbol' => 'ZZZT1',
        ]);

        $this->writeCsv('ZZZT1', [
            ['dividend_amount', 'declared_date', 'ex_dividend_date', 'record_date', 'payment_date', 'return_of_capital_percentage'],
            ['$0.2314', '2025-04-03', '2025-04-04', '2025-04-04', '2025-04-07', '0.0000'],
            ['$0.1987', '2025-04-10', '2025-04-11', '2025-04-11', '2025-04-14', '0.0000'],
        ]);

        $this->artisan('etfs:backfill-dividend-history', [
            'symbol' => 'ZZZT1',
        ])
            ->expectsOutput('Successfully imported dividend history for ZZZT1.')
            ->assertSuccessful();

        $this->assertSame(
            2,
            EtfDividendHistory::where('etf_id', $etf->id)->count()
        );

        $this->assertDatabaseHas('etf_dividend_histories', [
            'etf_id' => $etf->id,
            'dividend_amount' => '0.2314',
            'ex_dividend_date' => '2025-04-04',
            'payment_date' => '2025-04-07',
        ]);

        $this->assertDatabaseHas('etf_dividend_histories', [
            'etf_id' => $etf->id,
            'dividend_amount' => '0.1987',
            'ex_dividend_date' => '2025-04-11',
            'payment_date' => '2025-04-14',
        ]);
    }

    public function test_it_replaces_existing_dividend_history_when_command_runs(): void
    {
        $etf = Etf::factory()->create([
            'symbol' => 'ZZZT2',
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => 1.2500,
            'ex_dividend_date' => '2024-01-02',
            'payment_date' => '2024-01-05',
        ]);

        $this->writeCsv('ZZZT2', [
            ['dividend_amount', 'declared_date', 'ex_dividend_date', 'record_date', 'payment_date', 'return_of_capital_percentage'],
            ['$0.2314', '2025-04-03', '2025-04-04', '2025-04-04', '2025-04-07', '0.0000'],
        ]);

        $this->artisan('etfs:backfill-dividend-history', [
            'symbol' => 'ZZZT2',
        ])->assertSuccessful();

        $this->assertSame(
            1,
            EtfDividendHistory::where('etf_id', $etf->id)->count()
        );

        $this->assertDatabaseMissing('etf_dividend_histories', [
            'etf_id' => $etf->id,
            'ex_dividend_date' => '2024-01-02',
        ]);

        $this->assertDatabaseHas('etf_dividend_histories', [
            'etf_id' => $etf->id,
            'dividend_amount' => '0.2314',
            'ex_dividend_date' => '2025-04-04',
        ]);
    }

    public function test_it_fails_when_etf_symbol_does_not_exist(): void
    {
        $this->writeCsv('ZZZT3', [
            ['dividend_amount', 'declared_date', 'ex_dividend_date', 'record_date', 'payment_date', 'return_of_capital_percentage'],
            ['$0.2314', '2025-04-03', '2025-04-04', '2025-04-04', '2025-04-07', '0.0000'],
        ]);

        $this->artisan('etfs:backfill-dividend-history', [
            'symbol' => 'ZZZT3',
        ])
            ->expectsOutput('ETF with symbol [ZZZT3] was not found.')
            ->assertFailed();
    }

    public function test_it_fails_when_import_file_does_not_exist(): void
    {
        Etf::factory()->create([
            'symbol' => 'ZZZT4',
        ]);

        $expectedPath = $this->filePathForSymbol('ZZZT4');

        $this->artisan('etfs:backfill-dividend-history', [
            'symbol' => 'ZZZT4',
        ])
            ->expectsOutput("Import file not found at [{$expectedPath}].")
            ->assertFailed();
    }

    public function test_it_fails_when_csv_is_missing_required_columns(): void
    {
        Etf::factory()->create([
            'symbol' => 'ZZZT5',
        ]);

        $this->writeCsv('ZZZT5', [
            ['distribution', 'declared_date', 'ex_dividend_date', 'record_date', 'payment_date', 'return_of_capital_percentage'],
            ['$0.2314', '2025-04-03', '2025-04-04', '2025-04-04', '2025-04-07', '0.0000'],
        ]);

        $this->artisan('etfs:backfill-dividend-history', [
            'symbol' => 'ZZZT5',
        ])
            ->expectsOutput('Missing required column [dividend_amount].')
            ->assertFailed();
    }

    private function writeCsv(string $symbol, array $rows): void
    {
        $filePath = $this->filePathForSymbol($symbol);

        $this->testFiles[] = $filePath;

        $content = collect($rows)
            ->map(function (array $row) {
                return collect($row)
                    ->map(function ($value) {
                        $value = (string) $value;

                        return str_contains($value, ',')
                            ? "\"{$value}\""
                            : $value;
                    })
                    ->implode(',');
            })
            ->implode("\n");

        file_put_contents($filePath, $content);
    }

    private function filePathForSymbol(string $symbol): string
    {
        return app_path(
            'Imports/DividendData/' . strtolower($symbol) . '_dividends.csv'
        );
    }
}
