<?php

namespace Tests\Feature\PortfolioTransactions;

use App\Models\Etf;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\User;
use Database\Seeders\TransactionTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserBulkUploadTransactionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etfs')->truncate();
        DB::table('transaction_types')->truncate();

        $this->seed(TransactionTypeSeeder::class);
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etfs')->truncate();
        DB::table('transaction_types')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_bulk_upload_portfolio_transactions(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $schd = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $vym = Etf::factory()->create([
            'symbol' => 'VYM',
        ]);

        $file = $this->makeCsvFile([
            ['symbol', 'transaction_type', 'shares', 'price_per_share', 'transaction_date'],
            ['SCHD', 'buy', '10', '75.25', '2026-05-15'],
            ['VYM', 'sell', '5', '120.10', '2026-05-16'],
        ]);

        $response = $this->postJson("/api/csv-upload-portfolio-transactions/{$portfolio->id}", [
            'csv_file' => $file,
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'data' => [
                'total_rows' => 2,
                'imported_rows' => 2,
                'duplicate_rows' => 0,
                'failed_rows' => 0,
                'errors' => [],
            ],
        ]);

        $this->assertDatabaseHas('portfolio_transactions', [
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
            'transaction_type_id' => 1,
            'shares' => '10.0000',
            'price_per_share' => '75.2500',
            'transaction_date' => '2026-05-15',
        ]);

        $this->assertDatabaseHas('portfolio_transactions', [
            'portfolio_id' => $portfolio->id,
            'etf_id' => $vym->id,
            'transaction_type_id' => 2,
            'shares' => '5.0000',
            'price_per_share' => '120.1000',
            'transaction_date' => '2026-05-16',
        ]);
    }

    public function test_bulk_upload_requires_authentication(): void
    {
        $response = $this->postJson('/api/csv-upload-portfolio-transactions/1');

        $response->assertStatus(401);
    }

    public function test_bulk_upload_requires_csv_file(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson("/api/csv-upload-portfolio-transactions/{$portfolio->id}", []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'csv_file',
        ]);
    }

    public function test_bulk_upload_skips_duplicate_transactions(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $schd = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 75.25,
            'transaction_date' => '2026-05-15',
        ]);

        $file = $this->makeCsvFile([
            ['symbol', 'transaction_type', 'shares', 'price_per_share', 'transaction_date'],
            ['SCHD', 'buy', '10', '75.25', '2026-05-15'],
        ]);

        $response = $this->postJson("/api/csv-upload-portfolio-transactions/{$portfolio->id}", [
            'csv_file' => $file,
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('data.total_rows', 1);
        $response->assertJsonPath('data.imported_rows', 0);
        $response->assertJsonPath('data.duplicate_rows', 1);
        $response->assertJsonPath('data.failed_rows', 0);

        $this->assertSame(
            1,
            PortfolioTransaction::where('portfolio_id', $portfolio->id)
                ->where('etf_id', $schd->id)
                ->count()
        );
    }

    public function test_bulk_upload_reports_failed_rows(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $file = $this->makeCsvFile([
            ['symbol', 'transaction_type', 'shares', 'price_per_share', 'transaction_date'],
            ['NOPE', 'buy', '10', '75.25', '2026-05-15'],
            ['SCHD', 'transfer', '10', '75.25', '2026-05-16'],
            ['SCHD', 'buy', '0', '75.25', '2026-05-17'],
        ]);

        $response = $this->postJson("/api/csv-upload-portfolio-transactions/{$portfolio->id}", [
            'csv_file' => $file,
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('data.total_rows', 3);
        $response->assertJsonPath('data.imported_rows', 0);
        $response->assertJsonPath('data.duplicate_rows', 0);
        $response->assertJsonPath('data.failed_rows', 3);

        $response->assertJsonPath('data.errors.0.row', 2);
        $response->assertJsonPath('data.errors.0.message', 'ETF symbol [NOPE] was not found.');

        $response->assertJsonPath('data.errors.1.row', 3);
        $response->assertJsonPath('data.errors.1.message', 'Transaction type [transfer] is not supported.');

        $response->assertJsonPath('data.errors.2.row', 4);
        $response->assertJsonPath('data.errors.2.message', 'Shares must be greater than zero.');

        $this->assertSame(
            0,
            PortfolioTransaction::where('portfolio_id', $portfolio->id)->count()
        );
    }

    public function test_user_cannot_bulk_upload_transactions_to_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $file = $this->makeCsvFile([
            ['symbol', 'transaction_type', 'shares', 'price_per_share', 'transaction_date'],
            ['SCHD', 'buy', '10', '75.25', '2026-05-15'],
        ]);

        $response = $this->postJson("/api/csv-upload-portfolio-transactions/{$portfolio->id}", [
            'csv_file' => $file,
        ]);

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,
            'message' => 'The CSV format is invalid.',
            'required_columns' => [
                'symbol',
                'transaction_type',
                'shares',
                'price_per_share',
                'transaction_date',
            ],
            'example' => [
                'symbol' => 'SCHD',
                'transaction_type' => 'buy',
                'shares' => '10',
                'price_per_share' => '75.25',
                'transaction_date' => '2026-05-15',
            ],
        ]);

        $this->assertSame(
            0,
            PortfolioTransaction::where('portfolio_id', $portfolio->id)->count()
        );
    }

    private function makeCsvFile(array $rows): UploadedFile
    {
        $content = collect($rows)
            ->map(function (array $row) {
                return collect($row)
                    ->map(fn($value) => str_contains((string) $value, ',') ? "\"{$value}\"" : $value)
                    ->implode(',');
            })
            ->implode("\n");

        $path = tempnam(sys_get_temp_dir(), 'portfolio-transactions-import-');

        file_put_contents($path, $content);

        return new UploadedFile(
            $path,
            'portfolio_transactions.csv',
            'text/csv',
            null,
            true
        );
    }

    public function test_bulk_upload_accepts_header_aliases(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $schd = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $file = $this->makeCsvFile([
            ['stock', 'type', 'count', 'price', 'date'],
            ['SCHD', 'buy', '10', '75.25', '2026-05-15'],
        ]);

        $response = $this->postJson("/api/csv-upload-portfolio-transactions/{$portfolio->id}", [
            'csv_file' => $file,
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('data.total_rows', 1);
        $response->assertJsonPath('data.imported_rows', 1);
        $response->assertJsonPath('data.failed_rows', 0);

        $this->assertDatabaseHas('portfolio_transactions', [
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
            'transaction_type_id' => 1,
            'shares' => '10.0000',
            'price_per_share' => '75.2500',
            'transaction_date' => '2026-05-15',
        ]);
    }

    public function test_bulk_upload_normalizes_symbol_and_transaction_type_case(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $schd = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $file = $this->makeCsvFile([
            ['symbol', 'transaction_type', 'shares', 'price_per_share', 'transaction_date'],
            ['schd', 'PURCHASED', '10', '75.25', '2026-05-15'],
            ['schd', 'Sold', '5', '80', '2026-05-16'],
        ]);

        $response = $this->postJson("/api/csv-upload-portfolio-transactions/{$portfolio->id}", [
            'csv_file' => $file,
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('data.total_rows', 2);
        $response->assertJsonPath('data.imported_rows', 2);
        $response->assertJsonPath('data.failed_rows', 0);

        $this->assertDatabaseHas('portfolio_transactions', [
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
            'transaction_type_id' => 1,
            'shares' => '10.0000',
            'price_per_share' => '75.2500',
            'transaction_date' => '2026-05-15',
        ]);

        $this->assertDatabaseHas('portfolio_transactions', [
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
            'transaction_type_id' => 2,
            'shares' => '5.0000',
            'price_per_share' => '80.0000',
            'transaction_date' => '2026-05-16',
        ]);
    }

    public function test_bulk_upload_normalizes_currency_and_comma_formatted_numbers(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $schd = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $file = $this->makeCsvFile([
            ['ticker', 'action', 'quantity', 'cost', 'trade_date'],
            ['SCHD', 'buy', '1,000', '$75.25', '2026-05-15'],
        ]);

        $response = $this->postJson("/api/csv-upload-portfolio-transactions/{$portfolio->id}", [
            'csv_file' => $file,
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('data.total_rows', 1);
        $response->assertJsonPath('data.imported_rows', 1);
        $response->assertJsonPath('data.failed_rows', 0);

        $this->assertDatabaseHas('portfolio_transactions', [
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
            'transaction_type_id' => 1,
            'shares' => '1000.0000',
            'price_per_share' => '75.2500',
            'transaction_date' => '2026-05-15',
        ]);
    }
}
