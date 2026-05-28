<?php

namespace Tests\Unit\Imports;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\EtfPriceHistory;
use App\Services\Imports\ImportEtfPriceHistoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportEtfPriceHistoryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_imports_price_and_dividend_history_for_an_etf(): void
    {
        $etf = Etf::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $filePath = $this->makeTextFile([
            'Date',
            'Open',
            'High',
            'Low',
            'Close',
            'Adj Close',
            'Volume',

            'May 12, 2026',
            '0.239 Dividend',

            'May 15, 2026',
            '29.34',
            '29.53',
            '28.47',
            '28.58',
            '28.58',
            '190,900',

            'May 14, 2026',
            '29.64',
            '30.22',
            '29.33',
            '30.20',
            '30.20',
            '381,300',
        ]);

        $result = (new ImportEtfPriceHistoryService)->import(
            $etf->id,
            $filePath
        );

        $this->assertSame($etf->id, $result['etf_id']);
        $this->assertSame('CHPY', $result['symbol']);
        $this->assertSame(2, $result['rows_imported']);
        $this->assertSame(1, $result['dividend_rows_imported']);
        $this->assertSame('2026-05-14', $result['start_date']);
        $this->assertSame('2026-05-15', $result['end_date']);

        $this->assertDatabaseHas('etf_price_histories', [
            'etf_id' => $etf->id,
            'price_date' => '2026-05-15',
            'close_price' => '28.5800',
            'volume' => 190900,
        ]);

        $this->assertDatabaseHas('etf_price_histories', [
            'etf_id' => $etf->id,
            'price_date' => '2026-05-14',
            'close_price' => '30.2000',
            'volume' => 381300,
        ]);

        $this->assertDatabaseHas('etf_dividend_histories', [
            'etf_id' => $etf->id,
            'dividend_amount' => '0.2390',
            'ex_dividend_date' => '2026-05-12',
            'payment_date' => '2026-05-13',
            'data_source_id' => 1,
        ]);

        unlink($filePath);
    }

    public function test_it_replaces_existing_history_for_only_the_selected_etf(): void
    {
        $selectedEtf = Etf::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $otherEtf = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $selectedEtf->id,
            'price_date' => '2024-01-01',
            'close_price' => 10,
            'volume' => 100,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $selectedEtf->id,
            'dividend_amount' => '0.1000',
            'ex_dividend_date' => '2024-01-01',
            'data_source_id' => 1,
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $otherEtf->id,
            'price_date' => '2024-01-01',
            'close_price' => 20,
            'volume' => 200,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $otherEtf->id,
            'dividend_amount' => '0.2000',
            'ex_dividend_date' => '2024-01-01',
            'data_source_id' => 1,
        ]);

        $filePath = $this->makeTextFile([
            'Date',
            'Open',
            'High',
            'Low',
            'Close',
            'Adj Close',
            'Volume',

            'May 12, 2026',
            '0.239 Dividend',

            'May 15, 2026',
            '29.34',
            '29.53',
            '28.47',
            '28.58',
            '28.58',
            '190,900',
        ]);

        $result = (new ImportEtfPriceHistoryService)->import(
            $selectedEtf->id,
            $filePath
        );

        $this->assertSame(1, $result['rows_deleted']);
        $this->assertSame(1, $result['dividend_rows_deleted']);

        $this->assertDatabaseMissing('etf_price_histories', [
            'etf_id' => $selectedEtf->id,
            'price_date' => '2024-01-01',
        ]);

        $this->assertDatabaseMissing('etf_dividend_histories', [
            'etf_id' => $selectedEtf->id,
            'ex_dividend_date' => '2024-01-01',
        ]);

        $this->assertDatabaseHas('etf_price_histories', [
            'etf_id' => $selectedEtf->id,
            'price_date' => '2026-05-15',
            'close_price' => '28.5800',
            'volume' => 190900,
        ]);

        $this->assertDatabaseHas('etf_dividend_histories', [
            'etf_id' => $selectedEtf->id,
            'dividend_amount' => '0.2390',
            'ex_dividend_date' => '2026-05-12',
        ]);

        $this->assertDatabaseHas('etf_price_histories', [
            'etf_id' => $otherEtf->id,
            'price_date' => '2024-01-01',
            'close_price' => '20.0000',
            'volume' => 200,
        ]);

        $this->assertDatabaseHas('etf_dividend_histories', [
            'etf_id' => $otherEtf->id,
            'dividend_amount' => '0.2000',
            'ex_dividend_date' => '2024-01-01',
        ]);

        unlink($filePath);
    }

    public function test_it_imports_rows_in_chronological_order(): void
    {
        $etf = Etf::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $filePath = $this->makeTextFile([
            'Date',
            'Open',
            'High',
            'Low',
            'Close',
            'Adj Close',
            'Volume',

            'May 15, 2026',
            '29.34',
            '29.53',
            '28.47',
            '28.58',
            '28.58',
            '190,900',

            'May 14, 2026',
            '29.64',
            '30.22',
            '29.33',
            '30.20',
            '30.20',
            '381,300',
        ]);

        $result = (new ImportEtfPriceHistoryService)->import(
            $etf->id,
            $filePath
        );

        $this->assertSame('2026-05-14', $result['start_date']);
        $this->assertSame('2026-05-15', $result['end_date']);

        $records = EtfPriceHistory::where('etf_id', $etf->id)
            ->orderBy('id')
            ->get();

        $this->assertSame('2026-05-14', $records[0]->price_date->format('Y-m-d'));
        $this->assertSame('2026-05-15', $records[1]->price_date->format('Y-m-d'));

        unlink($filePath);
    }

    public function test_it_sorts_dividend_rows_in_chronological_order(): void
    {
        $etf = Etf::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $filePath = $this->makeTextFile([
            'Date',
            'Open',
            'High',
            'Low',
            'Close',
            'Adj Close',
            'Volume',

            'May 12, 2026',
            '0.239 Dividend',

            'May 5, 2026',
            '0.218 Dividend',

            'May 15, 2026',
            '29.34',
            '29.53',
            '28.47',
            '28.58',
            '28.58',
            '190,900',
        ]);

        (new ImportEtfPriceHistoryService)->import(
            $etf->id,
            $filePath
        );

        $records = EtfDividendHistory::where('etf_id', $etf->id)
            ->orderBy('id')
            ->get();

        $this->assertSame('2026-05-05', $records[0]->ex_dividend_date->format('Y-m-d'));
        $this->assertSame('2026-05-12', $records[1]->ex_dividend_date->format('Y-m-d'));

        unlink($filePath);
    }

    public function test_it_throws_exception_when_etf_does_not_exist(): void
    {
        $filePath = $this->makeTextFile([
            'Date',
            'Open',
            'High',
            'Low',
            'Close',
            'Adj Close',
            'Volume',

            'May 15, 2026',
            '29.34',
            '29.53',
            '28.47',
            '28.58',
            '28.58',
            '190,900',
        ]);

        $this->expectException(ModelNotFoundException::class);

        try {
            (new ImportEtfPriceHistoryService)->import(999999, $filePath);
        } finally {
            unlink($filePath);
        }
    }

    public function test_it_deletes_previous_price_and_dividend_history_records_before_importing_new_records(): void
    {
        $etf = Etf::factory()->create([
            'symbol' => 'CHPY',
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2024-01-01',
            'close_price' => 10,
            'volume' => 100,
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2024-01-02',
            'close_price' => 11,
            'volume' => 200,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.1000',
            'ex_dividend_date' => '2024-01-01',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.2000',
            'ex_dividend_date' => '2024-01-02',
            'data_source_id' => 1,
        ]);

        $this->assertSame(
            2,
            EtfPriceHistory::where('etf_id', $etf->id)->count()
        );

        $this->assertSame(
            2,
            EtfDividendHistory::where('etf_id', $etf->id)->count()
        );

        $filePath = $this->makeTextFile([
            'Date',
            'Open',
            'High',
            'Low',
            'Close',
            'Adj Close',
            'Volume',

            'May 12, 2026',
            '0.239 Dividend',

            'May 15, 2026',
            '29.34',
            '29.53',
            '28.47',
            '28.58',
            '28.58',
            '190,900',
        ]);

        (new ImportEtfPriceHistoryService)->import(
            $etf->id,
            $filePath
        );

        $this->assertSame(
            1,
            EtfPriceHistory::where('etf_id', $etf->id)->count()
        );

        $this->assertSame(
            1,
            EtfDividendHistory::where('etf_id', $etf->id)->count()
        );

        $this->assertDatabaseMissing('etf_price_histories', [
            'etf_id' => $etf->id,
            'price_date' => '2024-01-01',
        ]);

        $this->assertDatabaseMissing('etf_price_histories', [
            'etf_id' => $etf->id,
            'price_date' => '2024-01-02',
        ]);

        $this->assertDatabaseMissing('etf_dividend_histories', [
            'etf_id' => $etf->id,
            'ex_dividend_date' => '2024-01-01',
        ]);

        $this->assertDatabaseMissing('etf_dividend_histories', [
            'etf_id' => $etf->id,
            'ex_dividend_date' => '2024-01-02',
        ]);

        $this->assertDatabaseHas('etf_price_histories', [
            'etf_id' => $etf->id,
            'price_date' => '2026-05-15',
            'close_price' => '28.5800',
            'volume' => 190900,
        ]);

        $this->assertDatabaseHas('etf_dividend_histories', [
            'etf_id' => $etf->id,
            'dividend_amount' => '0.2390',
            'ex_dividend_date' => '2026-05-12',
        ]);

        unlink($filePath);
    }

    private function makeTextFile(array $lines): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'etf-price-history-service-import-');

        file_put_contents(
            $filePath,
            collect($lines)->implode(PHP_EOL)
        );

        return $filePath;
    }
}
