<?php

namespace Tests\Unit\Imports;

use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;
use App\Services\Imports\ImportSecurityPriceHistoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportSecurityPriceHistoryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();

        parent::tearDown();
    }

    public function test_it_imports_price_and_dividend_history_for_a_security(): void
    {
        $security = Security::factory()->create([
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

        $result = (new ImportSecurityPriceHistoryService)->import(
            $security->id,
            $filePath
        );

        $this->assertSame($security->id, $result['security_id']);
        $this->assertSame('CHPY', $result['symbol']);
        $this->assertSame(2, $result['rows_imported']);
        $this->assertSame(1, $result['dividend_rows_imported']);
        $this->assertSame('2026-05-14', $result['start_date']);
        $this->assertSame('2026-05-15', $result['end_date']);

        $this->assertDatabaseHas('security_price_histories', [
            'security_id' => $security->id,
            'price_date' => '2026-05-15',
            'close_price' => '28.5800',
            'volume' => 190900,
        ]);

        $this->assertDatabaseHas('security_price_histories', [
            'security_id' => $security->id,
            'price_date' => '2026-05-14',
            'close_price' => '30.2000',
            'volume' => 381300,
        ]);

        $this->assertDatabaseHas('security_dividend_histories', [
            'security_id' => $security->id,
            'dividend_amount' => '0.2390',
            'ex_dividend_date' => '2026-05-12',
            'payment_date' => '2026-05-13',
            'data_source_id' => 1,
        ]);

        unlink($filePath);
    }

    public function test_it_replaces_existing_history_for_only_the_selected_security(): void
    {
        $selectedSecurity = Security::factory()->create([
            'symbol' => 'CHPY',
        ]);

        $otherSecurity = Security::factory()->create([
            'symbol' => 'SCHD',
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $selectedSecurity->id,
            'price_date' => '2024-01-01',
            'close_price' => 10,
            'volume' => 100,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $selectedSecurity->id,
            'dividend_amount' => '0.1000',
            'ex_dividend_date' => '2024-01-01',
            'data_source_id' => 1,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $otherSecurity->id,
            'price_date' => '2024-01-01',
            'close_price' => 20,
            'volume' => 200,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $otherSecurity->id,
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

        $result = (new ImportSecurityPriceHistoryService)->import(
            $selectedSecurity->id,
            $filePath
        );

        $this->assertSame(1, $result['rows_deleted']);
        $this->assertSame(1, $result['dividend_rows_deleted']);

        $this->assertDatabaseMissing('security_price_histories', [
            'security_id' => $selectedSecurity->id,
            'price_date' => '2024-01-01',
        ]);

        $this->assertDatabaseMissing('security_dividend_histories', [
            'security_id' => $selectedSecurity->id,
            'ex_dividend_date' => '2024-01-01',
        ]);

        $this->assertDatabaseHas('security_price_histories', [
            'security_id' => $selectedSecurity->id,
            'price_date' => '2026-05-15',
            'close_price' => '28.5800',
            'volume' => 190900,
        ]);

        $this->assertDatabaseHas('security_dividend_histories', [
            'security_id' => $selectedSecurity->id,
            'dividend_amount' => '0.2390',
            'ex_dividend_date' => '2026-05-12',
        ]);

        $this->assertDatabaseHas('security_price_histories', [
            'security_id' => $otherSecurity->id,
            'price_date' => '2024-01-01',
            'close_price' => '20.0000',
            'volume' => 200,
        ]);

        $this->assertDatabaseHas('security_dividend_histories', [
            'security_id' => $otherSecurity->id,
            'dividend_amount' => '0.2000',
            'ex_dividend_date' => '2024-01-01',
        ]);

        unlink($filePath);
    }

    public function test_it_imports_rows_in_chronological_order(): void
    {
        $security = Security::factory()->create([
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

        $result = (new ImportSecurityPriceHistoryService)->import(
            $security->id,
            $filePath
        );

        $this->assertSame('2026-05-14', $result['start_date']);
        $this->assertSame('2026-05-15', $result['end_date']);

        $records = SecurityPriceHistory::where('security_id', $security->id)
            ->orderBy('id')
            ->get();

        $this->assertSame('2026-05-14', $records[0]->price_date->format('Y-m-d'));
        $this->assertSame('2026-05-15', $records[1]->price_date->format('Y-m-d'));

        unlink($filePath);
    }

    public function test_it_sorts_dividend_rows_in_chronological_order(): void
    {
        $security = Security::factory()->create([
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

        (new ImportSecurityPriceHistoryService)->import(
            $security->id,
            $filePath
        );

        $records = SecurityDividendHistory::where('security_id', $security->id)
            ->orderBy('id')
            ->get();

        $this->assertSame('2026-05-05', $records[0]->ex_dividend_date->format('Y-m-d'));
        $this->assertSame('2026-05-12', $records[1]->ex_dividend_date->format('Y-m-d'));

        unlink($filePath);
    }

    public function test_it_throws_exception_when_security_does_not_exist(): void
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
            (new ImportSecurityPriceHistoryService)->import(999999, $filePath);
        } finally {
            unlink($filePath);
        }
    }

    public function test_it_deletes_previous_price_and_dividend_history_records_before_importing_new_records(): void
    {
        $security = Security::factory()->create([
            'symbol' => 'CHPY',
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2024-01-01',
            'close_price' => 10,
            'volume' => 100,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2024-01-02',
            'close_price' => 11,
            'volume' => 200,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.1000',
            'ex_dividend_date' => '2024-01-01',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.2000',
            'ex_dividend_date' => '2024-01-02',
            'data_source_id' => 1,
        ]);

        $this->assertSame(
            2,
            SecurityPriceHistory::where('security_id', $security->id)->count()
        );

        $this->assertSame(
            2,
            SecurityDividendHistory::where('security_id', $security->id)->count()
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

        (new ImportSecurityPriceHistoryService)->import(
            $security->id,
            $filePath
        );

        $this->assertSame(
            1,
            SecurityPriceHistory::where('security_id', $security->id)->count()
        );

        $this->assertSame(
            1,
            SecurityDividendHistory::where('security_id', $security->id)->count()
        );

        $this->assertDatabaseMissing('security_price_histories', [
            'security_id' => $security->id,
            'price_date' => '2024-01-01',
        ]);

        $this->assertDatabaseMissing('security_price_histories', [
            'security_id' => $security->id,
            'price_date' => '2024-01-02',
        ]);

        $this->assertDatabaseMissing('security_dividend_histories', [
            'security_id' => $security->id,
            'ex_dividend_date' => '2024-01-01',
        ]);

        $this->assertDatabaseMissing('security_dividend_histories', [
            'security_id' => $security->id,
            'ex_dividend_date' => '2024-01-02',
        ]);

        $this->assertDatabaseHas('security_price_histories', [
            'security_id' => $security->id,
            'price_date' => '2026-05-15',
            'close_price' => '28.5800',
            'volume' => 190900,
        ]);

        $this->assertDatabaseHas('security_dividend_histories', [
            'security_id' => $security->id,
            'dividend_amount' => '0.2390',
            'ex_dividend_date' => '2026-05-12',
        ]);

        unlink($filePath);
    }

    private function makeTextFile(array $lines): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'security-price-history-service-import-');

        file_put_contents(
            $filePath,
            collect($lines)->implode(PHP_EOL)
        );

        return $filePath;
    }
}
