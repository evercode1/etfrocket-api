<?php

namespace Tests\Unit\Imports;

use App\Models\Etf;
use App\Models\EtfPriceHistory;
use App\Services\Imports\ImportEtfPriceHistoryService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportEtfPriceHistoryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_imports_price_history_for_an_etf(): void
    {
        $etf = Etf::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $filePath = $this->makeCsvFile([
            ['Date', 'Open', 'High', 'Low', 'Close', 'Volume'],
            ['2025-04-04', '44.0000', '44.00', '42.2194', '42.2194', '2312'],
            ['2025-04-07', '40.3953', '43.79', '40.3953', '43.1155', '1284'],
        ]);

        $result = (new ImportEtfPriceHistoryService())->import(
            $etf->id,
            $filePath
        );

        $this->assertSame($etf->id, $result['etf_id']);
        $this->assertSame('CHPY', $result['symbol']);
        $this->assertSame(2, $result['rows_imported']);
        $this->assertSame('2025-04-04', $result['start_date']);
        $this->assertSame('2025-04-07', $result['end_date']);

        $this->assertDatabaseHas('etf_price_histories', [
            'etf_id' => $etf->id,
            'price_date' => '2025-04-04',
            'close_price' => '42.2194',
            'volume' => 2312,
        ]);

        $this->assertDatabaseHas('etf_price_histories', [
            'etf_id' => $etf->id,
            'price_date' => '2025-04-07',
            'close_price' => '43.1155',
            'volume' => 1284,
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

        EtfPriceHistory::factory()->create([
            'etf_id' => $otherEtf->id,
            'price_date' => '2024-01-01',
            'close_price' => 20,
            'volume' => 200,
        ]);

        $filePath = $this->makeCsvFile([
            ['Date', 'Open', 'High', 'Low', 'Close', 'Volume'],
            ['2025-04-04', '44.0000', '44.00', '42.2194', '42.2194', '2312'],
        ]);

        (new ImportEtfPriceHistoryService())->import(
            $selectedEtf->id,
            $filePath
        );

        $this->assertDatabaseMissing('etf_price_histories', [
            'etf_id' => $selectedEtf->id,
            'price_date' => '2024-01-01',
        ]);

        $this->assertDatabaseHas('etf_price_histories', [
            'etf_id' => $selectedEtf->id,
            'price_date' => '2025-04-04',
            'close_price' => '42.2194',
            'volume' => 2312,
        ]);

        $this->assertDatabaseHas('etf_price_histories', [
            'etf_id' => $otherEtf->id,
            'price_date' => '2024-01-01',
            'close_price' => '20.0000',
            'volume' => 200,
        ]);

        unlink($filePath);
    }

    public function test_it_imports_rows_in_chronological_order(): void
    {
        $etf = Etf::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $filePath = $this->makeCsvFile([
            ['Date', 'Open', 'High', 'Low', 'Close', 'Volume'],
            ['2025-04-07', '40.3953', '43.79', '40.3953', '43.1155', '1284'],
            ['2025-04-04', '44.0000', '44.00', '42.2194', '42.2194', '2312'],
        ]);

        $result = (new ImportEtfPriceHistoryService())->import(
            $etf->id,
            $filePath
        );

        $this->assertSame('2025-04-04', $result['start_date']);
        $this->assertSame('2025-04-07', $result['end_date']);

        $records = EtfPriceHistory::where('etf_id', $etf->id)
            ->orderBy('id')
            ->get();

        $this->assertSame('2025-04-04', $records[0]->price_date->format('Y-m-d'));
        $this->assertSame('2025-04-07', $records[1]->price_date->format('Y-m-d'));

        unlink($filePath);
    }

    public function test_it_throws_exception_when_etf_does_not_exist(): void
    {
        $filePath = $this->makeCsvFile([
            ['Date', 'Open', 'High', 'Low', 'Close', 'Volume'],
            ['2025-04-04', '44.0000', '44.00', '42.2194', '42.2194', '2312'],
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        try {
            (new ImportEtfPriceHistoryService())->import(999999, $filePath);
        } finally {
            unlink($filePath);
        }
    }

    private function makeCsvFile(array $rows): string
    {
        $content = collect($rows)
            ->map(function (array $row) {
                return collect($row)
                    ->map(fn($value) => str_contains((string) $value, ',') ? "\"{$value}\"" : $value)
                    ->implode(',');
            })
            ->implode("\n");

        $filePath = tempnam(sys_get_temp_dir(), 'etf-price-history-service-import-');

        file_put_contents($filePath, $content);

        return $filePath;
    }

    public function test_it_deletes_previous_price_history_records_before_importing_new_records(): void
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

        $this->assertSame(
            2,
            EtfPriceHistory::where('etf_id', $etf->id)->count()
        );

        $filePath = $this->makeCsvFile([
            ['Date', 'Open', 'High', 'Low', 'Close', 'Volume'],
            ['2025-04-04', '44.0000', '44.00', '42.2194', '42.2194', '2312'],
        ]);

        (new ImportEtfPriceHistoryService())->import(
            $etf->id,
            $filePath
        );

        $this->assertSame(
            1,
            EtfPriceHistory::where('etf_id', $etf->id)->count()
        );

        $this->assertDatabaseMissing('etf_price_histories', [
            'etf_id' => $etf->id,
            'price_date' => '2024-01-01',
        ]);

        $this->assertDatabaseMissing('etf_price_histories', [
            'etf_id' => $etf->id,
            'price_date' => '2024-01-02',
        ]);

        $this->assertDatabaseHas('etf_price_histories', [
            'etf_id' => $etf->id,
            'price_date' => '2025-04-04',
            'close_price' => '42.2194',
            'volume' => 2312,
        ]);

        unlink($filePath);
    }
}
