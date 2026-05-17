<?php

namespace Tests\Unit\Imports;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Services\Imports\ImportEtfDividendHistoryService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportEtfDividendHistoryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_imports_dividend_history_for_an_etf(): void
    {
        $etf = Etf::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $filePath = $this->makeCsvFile([
            ['dividend_amount', 'declared_date', 'ex_dividend_date', 'record_date', 'payment_date', 'return_of_capital_percentage'],
            ['$0.2314', '2025-04-03', '2025-04-04', '2025-04-04', '2025-04-07', '0.0000'],
            ['$0.1987', '2025-04-10', '2025-04-11', '2025-04-11', '2025-04-14', '0.0000'],
        ]);

        $result = (new ImportEtfDividendHistoryService())->import(
            $etf->id,
            $filePath
        );

        $this->assertSame($etf->id, $result['etf_id']);
        $this->assertSame('CHPY', $result['symbol']);
        $this->assertSame(0, $result['rows_deleted']);
        $this->assertSame(2, $result['rows_imported']);
        $this->assertSame('2025-04-04', $result['start_date']);
        $this->assertSame('2025-04-11', $result['end_date']);

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

        unlink($filePath);
    }

    public function test_it_replaces_existing_dividend_history_for_only_the_selected_etf(): void
    {
        $selectedEtf = Etf::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $otherEtf = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $selectedEtf->id,
            'dividend_amount' => 1.25,
            'ex_dividend_date' => '2024-01-02',
            'payment_date' => '2024-01-05',
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $otherEtf->id,
            'dividend_amount' => 2.50,
            'ex_dividend_date' => '2024-01-02',
            'payment_date' => '2024-01-05',
        ]);

        $filePath = $this->makeCsvFile([
            ['dividend_amount', 'declared_date', 'ex_dividend_date', 'record_date', 'payment_date', 'return_of_capital_percentage'],
            ['$0.2314', '2025-04-03', '2025-04-04', '2025-04-04', '2025-04-07', '0.0000'],
        ]);

        $result = (new ImportEtfDividendHistoryService())->import(
            $selectedEtf->id,
            $filePath
        );

        $this->assertSame(1, $result['rows_deleted']);
        $this->assertSame(1, $result['rows_imported']);

        $this->assertDatabaseMissing('etf_dividend_histories', [
            'etf_id' => $selectedEtf->id,
            'ex_dividend_date' => '2024-01-02',
        ]);

        $this->assertDatabaseHas('etf_dividend_histories', [
            'etf_id' => $selectedEtf->id,
            'dividend_amount' => '0.2314',
            'ex_dividend_date' => '2025-04-04',
        ]);

        $this->assertDatabaseHas('etf_dividend_histories', [
            'etf_id' => $otherEtf->id,
            'dividend_amount' => '2.5000',
            'ex_dividend_date' => '2024-01-02',
        ]);

        unlink($filePath);
    }

    public function test_it_deletes_previous_dividend_history_records_before_importing_new_records(): void
    {
        $etf = Etf::factory()->create([
            'symbol' => 'CHPY',
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => 1.25,
            'ex_dividend_date' => '2024-01-02',
            'payment_date' => '2024-01-05',
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => 1.50,
            'ex_dividend_date' => '2024-02-02',
            'payment_date' => '2024-02-05',
        ]);

        $this->assertSame(
            2,
            EtfDividendHistory::where('etf_id', $etf->id)->count()
        );

        $filePath = $this->makeCsvFile([
            ['dividend_amount', 'declared_date', 'ex_dividend_date', 'record_date', 'payment_date', 'return_of_capital_percentage'],
            ['$0.2314', '2025-04-03', '2025-04-04', '2025-04-04', '2025-04-07', '0.0000'],
        ]);

        (new ImportEtfDividendHistoryService())->import(
            $etf->id,
            $filePath
        );

        $this->assertSame(
            1,
            EtfDividendHistory::where('etf_id', $etf->id)->count()
        );

        $this->assertDatabaseMissing('etf_dividend_histories', [
            'etf_id' => $etf->id,
            'ex_dividend_date' => '2024-01-02',
        ]);

        $this->assertDatabaseMissing('etf_dividend_histories', [
            'etf_id' => $etf->id,
            'ex_dividend_date' => '2024-02-02',
        ]);

        $this->assertDatabaseHas('etf_dividend_histories', [
            'etf_id' => $etf->id,
            'dividend_amount' => '0.2314',
            'ex_dividend_date' => '2025-04-04',
        ]);

        unlink($filePath);
    }

    public function test_it_imports_rows_in_chronological_order_by_ex_dividend_date(): void
    {
        $etf = Etf::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $filePath = $this->makeCsvFile([
            ['dividend_amount', 'declared_date', 'ex_dividend_date', 'record_date', 'payment_date', 'return_of_capital_percentage'],
            ['$0.1987', '2025-04-10', '2025-04-11', '2025-04-11', '2025-04-14', '0.0000'],
            ['$0.2314', '2025-04-03', '2025-04-04', '2025-04-04', '2025-04-07', '0.0000'],
        ]);

        $result = (new ImportEtfDividendHistoryService())->import(
            $etf->id,
            $filePath
        );

        $this->assertSame('2025-04-04', $result['start_date']);
        $this->assertSame('2025-04-11', $result['end_date']);

        $records = EtfDividendHistory::where('etf_id', $etf->id)
            ->orderBy('id')
            ->get();

        $this->assertSame('2025-04-04', $records[0]->ex_dividend_date->format('Y-m-d'));
        $this->assertSame('2025-04-11', $records[1]->ex_dividend_date->format('Y-m-d'));

        unlink($filePath);
    }

    public function test_it_throws_exception_when_etf_does_not_exist(): void
    {
        $filePath = $this->makeCsvFile([
            ['dividend_amount', 'declared_date', 'ex_dividend_date', 'record_date', 'payment_date', 'return_of_capital_percentage'],
            ['$0.2314', '2025-04-03', '2025-04-04', '2025-04-04', '2025-04-07', '0.0000'],
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        try {
            (new ImportEtfDividendHistoryService())->import(999999, $filePath);
        } finally {
            unlink($filePath);
        }
    }

    private function makeCsvFile(array $rows): string
    {
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

        $filePath = tempnam(sys_get_temp_dir(), 'etf-dividend-history-service-import-');

        file_put_contents($filePath, $content);

        return $filePath;
    }
}
