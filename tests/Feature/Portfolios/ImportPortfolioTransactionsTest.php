<?php

namespace Tests\Feature\Portfolios;

use App\Models\Etf;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\User;
use Database\Seeders\TransactionTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImportPortfolioTransactionsTest extends TestCase
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

    public function test_authenticated_user_can_import_portfolio_transactions_from_csv(): void
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

        $response = $this->postJson("/api/import-portfolio-transactions/{$portfolio->id}", [
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

    public function test_import_skips_duplicate_transactions(): void
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

        $response = $this->postJson("/api/import-portfolio-transactions/{$portfolio->id}", [
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

    public function test_import_reports_invalid_etf_symbol_as_failed_row(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $file = $this->makeCsvFile([
            ['symbol', 'transaction_type', 'shares', 'price_per_share', 'transaction_date'],
            ['NOPE', 'buy', '10', '75.25', '2026-05-15'],
        ]);

        $response = $this->postJson("/api/import-portfolio-transactions/{$portfolio->id}", [
            'csv_file' => $file,
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('data.total_rows', 1);
        $response->assertJsonPath('data.imported_rows', 0);
        $response->assertJsonPath('data.duplicate_rows', 0);
        $response->assertJsonPath('data.failed_rows', 1);
        $response->assertJsonPath('data.errors.0.row', 2);
        $response->assertJsonPath('data.errors.0.message', 'ETF symbol [NOPE] was not found.');

        $this->assertSame(0, PortfolioTransaction::where('portfolio_id', $portfolio->id)->count());
    }

    public function test_import_reports_invalid_transaction_type_as_failed_row(): void
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
            ['SCHD', 'transfer', '10', '75.25', '2026-05-15'],
        ]);

        $response = $this->postJson("/api/import-portfolio-transactions/{$portfolio->id}", [
            'csv_file' => $file,
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('data.total_rows', 1);
        $response->assertJsonPath('data.imported_rows', 0);
        $response->assertJsonPath('data.failed_rows', 1);
        $response->assertJsonPath('data.errors.0.row', 2);
        $response->assertJsonPath('data.errors.0.message', 'Transaction type [transfer] is not supported.');

        $this->assertSame(0, PortfolioTransaction::where('portfolio_id', $portfolio->id)->count());
    }

    public function test_import_reports_invalid_numeric_values_as_failed_rows(): void
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
            ['SCHD', 'buy', '0', '75.25', '2026-05-15'],
            ['SCHD', 'buy', '10', '-1', '2026-05-16'],
        ]);

        $response = $this->postJson("/api/import-portfolio-transactions/{$portfolio->id}", [
            'csv_file' => $file,
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('data.total_rows', 2);
        $response->assertJsonPath('data.imported_rows', 0);
        $response->assertJsonPath('data.failed_rows', 2);
        $response->assertJsonPath('data.errors.0.row', 2);
        $response->assertJsonPath('data.errors.0.message', 'Shares must be greater than zero.');
        $response->assertJsonPath('data.errors.1.row', 3);
        $response->assertJsonPath('data.errors.1.message', 'Price per share cannot be negative.');
    }

    public function test_import_accepts_transaction_type_aliases_case_insensitively(): void
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
            ['SCHD', 'PURCHASED', '10', '75.25', '2026-05-15'],
            ['SCHD', 'Sold', '5', '80', '2026-05-16'],
        ]);

        $response = $this->postJson("/api/import-portfolio-transactions/{$portfolio->id}", [
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
            'transaction_date' => '2026-05-15',
        ]);

        $this->assertDatabaseHas('portfolio_transactions', [
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
            'transaction_type_id' => 2,
            'transaction_date' => '2026-05-16',
        ]);
    }

    public function test_guest_cannot_import_portfolio_transactions(): void
    {
        $response = $this->postJson('/api/import-portfolio-transactions/1');

        $response->assertStatus(401);
    }

    public function test_import_requires_csv_file(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson("/api/import-portfolio-transactions/{$portfolio->id}", []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'csv_file',
        ]);
    }

    public function test_user_cannot_import_transactions_into_another_users_portfolio(): void
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

        $response = $this->postJson("/api/import-portfolio-transactions/{$portfolio->id}", [
            'csv_file' => $file,
        ]);

        $response->assertStatus(422);

        $response->assertJson([
            'success' => false,

            'message' => 'The CSV format is invalid.',

            'required_columns' => [

                'symbol',

                'type',

                'shares',

                'price',

                'date',

            ],

            'example' => [

                'symbol' => 'SCHD',

                'type' => 'buy',

                'shares' => '10',

                'price' => '75.25',

                'date' => '2026-05-15',

            ],
        ]);

        $this->assertSame(0, PortfolioTransaction::where('portfolio_id', $portfolio->id)->count());
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

    public function test_authenticated_user_can_delete_all_portfolio_transactions(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
        ]);

        $response = $this->deleteJson("/api/delete-all-portfolio-transactions/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'message' => 'All portfolio transactions deleted successfully.',
        ]);

        $this->assertSame(
            0,
            PortfolioTransaction::where('portfolio_id', $portfolio->id)->count()
        );
    }

    public function test_user_cannot_delete_all_transactions_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
        ]);

        $response = $this->deleteJson("/api/delete-all-portfolio-transactions/{$portfolio->id}");

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
        ]);

        $this->assertSame(
            1,
            PortfolioTransaction::where('portfolio_id', $portfolio->id)->count()
        );
    }

    public function test_authenticated_user_can_get_import_portfolio_transactions_config(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/get-import-portfolio-transactions-config');

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.required_columns.0', 'symbol');
        $response->assertJsonPath('data.required_columns.1', 'transaction_type');
        $response->assertJsonPath('data.required_columns.2', 'shares');
        $response->assertJsonPath('data.required_columns.3', 'price_per_share');
        $response->assertJsonPath('data.required_columns.4', 'transaction_date');

        $response->assertJsonPath('data.date_format', 'Y-m-d');

        $response->assertJsonPath('data.example_row.symbol', 'SCHD');
        $response->assertJsonPath('data.example_row.transaction_type', 'buy');
        $response->assertJsonPath('data.example_row.shares', '10');
        $response->assertJsonPath('data.example_row.price_per_share', '75.25');
        $response->assertJsonPath('data.example_row.transaction_date', '2026-05-15');

        $this->assertContains('buy', $response->json('data.accepted_transaction_types'));
        $this->assertContains('sell', $response->json('data.accepted_transaction_types'));

        $this->assertContains(
            'symbol,transaction_type,shares,price_per_share,transaction_date',
            $response->json('data.example_csv')
        );
    }

    public function test_guest_cannot_get_import_portfolio_transactions_config(): void
    {
        $response = $this->getJson('/api/get-import-portfolio-transactions-config');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_export_portfolio_transactions(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $schd = Etf::factory()->create(['symbol' => 'SCHD']);
        $vym = Etf::factory()->create(['symbol' => 'VYM']);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 75.25,
            'transaction_date' => '2026-05-15',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $vym->id,
            'transaction_type_id' => 2,
            'shares' => 5,
            'price_per_share' => 120.10,
            'transaction_date' => '2026-05-16',
        ]);

        $response = $this->get("/api/export-portfolio-transactions/{$portfolio->id}");

        $response->assertStatus(200);

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'symbol,transaction_type,shares,price_per_share,transaction_date',
            $content
        );

        $this->assertStringContainsString('SCHD,buy', $content);
        $this->assertStringContainsString('VYM,sell', $content);
    }

    public function test_export_portfolio_transactions_can_filter_by_etf(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
        ]);

        $schd = Etf::factory()->create(['symbol' => 'SCHD']);
        $vym = Etf::factory()->create(['symbol' => 'VYM']);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $schd->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 75.25,
            'transaction_date' => '2026-05-15',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $vym->id,
            'transaction_type_id' => 2,
            'shares' => 5,
            'price_per_share' => 120.10,
            'transaction_date' => '2026-05-16',
        ]);

        $response = $this->get("/api/export-portfolio-transactions/{$portfolio->id}?etf_id={$schd->id}");

        $response->assertStatus(200);

        $content = $response->streamedContent();

        $this->assertStringContainsString('SCHD,buy', $content);
        $this->assertStringNotContainsString('VYM,sell', $content);
    }

    public function test_guest_cannot_export_portfolio_transactions(): void
    {
        $response = $this->getJson('/api/export-portfolio-transactions/1');

        $response->assertStatus(401);
    }

    public function test_user_cannot_export_transactions_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->get("/api/export-portfolio-transactions/{$portfolio->id}");

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
        ]);
    }
}
