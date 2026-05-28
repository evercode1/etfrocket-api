<?php

namespace Tests\Feature\Portfolios;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortfolioTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_list_portfolio_transactions(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $security = Security::factory()->create(['symbol' => 'SCHD']);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_date' => '2026-05-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_date' => '2026-05-10',
        ]);

        $response = $this->getJson("/api/list-portfolio-transactions/{$portfolio->id}?sortBy=1&sortOrder=desc");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertCount(2, $response->json('data.data'));

        $response->assertJsonPath('data.data.0.transaction_date', '2026-05-10');
        $response->assertJsonPath('data.data.1.transaction_date', '2026-05-01');
    }

    public function test_list_portfolio_transactions_can_filter_by_etf(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $schd = Security::factory()->create(['symbol' => 'SCHD']);
        $vym = Security::factory()->create(['symbol' => 'VYM']);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $schd->id,
            'transaction_date' => '2026-05-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $vym->id,
            'transaction_date' => '2026-05-02',
        ]);

        $response = $this->getJson("/api/list-portfolio-transactions/{$portfolio->id}?security_id={$schd->id}&sortBy=1&sortOrder=desc");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertCount(1, $response->json('data.data'));
        $response->assertJsonPath('data.data.0.security_id', $schd->id);
    }

    public function test_guest_cannot_list_portfolio_transactions(): void
    {
        $response = $this->getJson('/api/list-portfolio-transactions/1');

        $response->assertStatus(401);
    }

    public function test_user_cannot_list_transactions_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/list-portfolio-transactions/{$portfolio->id}");

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
    }

    public function test_authenticated_user_can_get_create_transaction_form_config(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/api/get-create-portfolio-transaction-form-config/{$portfolio->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $response->assertJsonPath('data.portfolio_id', $portfolio->id);
        $response->assertJsonPath('data.fields.0.name', 'security_id');
        $response->assertJsonPath('data.fields.1.name', 'transaction_type_id');
        $response->assertJsonPath('data.fields.2.name', 'shares');
        $response->assertJsonPath('data.fields.3.name', 'price_per_share');
        $response->assertJsonPath('data.fields.4.name', 'transaction_date');
    }

    public function test_user_cannot_get_create_transaction_form_config_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/get-create-portfolio-transaction-form-config/{$portfolio->id}");

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
    }

    public function test_authenticated_user_can_create_portfolio_transaction(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $security = Security::factory()->create(['symbol' => 'SCHD']);

        $response = $this->postJson("/api/create-portfolio-transaction/{$portfolio->id}", [
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 10.5,
            'price_per_share' => 75.25,
            'transaction_date' => '2026-05-15',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        $response->assertJsonPath('data.portfolio_id', $portfolio->id);
        $response->assertJsonPath('data.security_id', $security->id);
        $response->assertJsonPath('data.transaction_type_id', 1);

        $this->assertDatabaseHas('portfolio_transactions', [
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'transaction_date' => '2026-05-15',
        ]);
    }

    public function test_create_portfolio_transaction_requires_required_fields(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson("/api/create-portfolio-transaction/{$portfolio->id}", []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'security_id',
            'transaction_type_id',
            'shares',
            'price_per_share',
            'transaction_date',
        ]);
    }

    public function test_user_cannot_create_transaction_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $otherUser->id]);
        $security = Security::factory()->create();

        $response = $this->postJson("/api/create-portfolio-transaction/{$portfolio->id}", [
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 50,
            'transaction_date' => '2026-05-15',
        ]);

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);

        $this->assertDatabaseMissing('portfolio_transactions', [
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
        ]);
    }

    public function test_authenticated_user_can_get_update_transaction_form_config(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $security = Security::factory()->create();

        $transaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 12.5,
            'price_per_share' => 80.75,
            'transaction_date' => '2026-05-15',
        ]);

        $response = $this->getJson("/api/get-update-portfolio-transaction-form-config/{$transaction->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $response->assertJsonPath('data.portfolio_transaction_id', $transaction->id);
        $response->assertJsonPath('data.portfolio_id', $portfolio->id);
        $response->assertJsonPath('data.fields.0.value', $security->id);
        $response->assertJsonPath('data.fields.1.value', 1);
        $response->assertJsonPath('data.fields.4.value', '2026-05-15');
    }

    public function test_user_cannot_get_update_form_for_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $otherUser->id]);

        $transaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
        ]);

        $response = $this->getJson("/api/get-update-portfolio-transaction-form-config/{$transaction->id}");

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
    }

    public function test_authenticated_user_can_update_portfolio_transaction(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $oldSecurity = Security::factory()->create(['symbol' => 'OLD']);
        $newSecurity = Security::factory()->create(['symbol' => 'NEW']);

        $transaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $oldSecurity->id,
            'transaction_type_id' => 1,
            'shares' => 5,
            'price_per_share' => 50,
            'transaction_date' => '2026-05-01',
        ]);

        $response = $this->putJson("/api/update-portfolio-transaction/{$transaction->id}", [
            'security_id' => $newSecurity->id,
            'transaction_type_id' => 2,
            'shares' => 8,
            'price_per_share' => 60,
            'transaction_date' => '2026-05-10',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $response->assertJsonPath('data.security_id', $newSecurity->id);
        $response->assertJsonPath('data.transaction_type_id', 2);
        $response->assertJsonPath('data.transaction_date', '2026-05-10');

        $this->assertDatabaseHas('portfolio_transactions', [
            'id' => $transaction->id,
            'security_id' => $newSecurity->id,
            'transaction_type_id' => 2,
            'transaction_date' => '2026-05-10',
        ]);
    }

    public function test_user_cannot_update_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $otherUser->id]);

        $transaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'shares' => 5,
        ]);

        $response = $this->putJson("/api/update-portfolio-transaction/{$transaction->id}", [
            'shares' => 99,
        ]);

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);

        $this->assertDatabaseMissing('portfolio_transactions', [
            'id' => $transaction->id,
            'shares' => 99,
        ]);
    }

    public function test_authenticated_user_can_delete_portfolio_transaction(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $transaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
        ]);

        $response = $this->deleteJson("/api/delete-portfolio-transaction/{$transaction->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'message' => 'Portfolio transaction deleted successfully.',
        ]);

        $this->assertDatabaseMissing('portfolio_transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create(['user_id' => $otherUser->id]);

        $transaction = PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
        ]);

        $response = $this->deleteJson("/api/delete-portfolio-transaction/{$transaction->id}");

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);

        $this->assertDatabaseHas('portfolio_transactions', [
            'id' => $transaction->id,
        ]);
    }
}
