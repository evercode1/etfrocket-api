<?php

namespace Tests\Feature\Portfolios;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortfoliosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_list_their_portfolios(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Growth Portfolio',
            'is_default' => false,
        ]);

        Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Default Portfolio',
            'is_default' => true,
        ]);

        Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'portfolio_name' => 'Other User Portfolio',
            'is_default' => true,
        ]);

        $response = $this->getJson('/api/list-portfolios');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $response->assertJsonPath('data.0.portfolio_name', 'Default Portfolio');
        $response->assertJsonPath('data.1.portfolio_name', 'Growth Portfolio');

        $this->assertCount(2, $response->json('data'));
    }

    public function test_guest_cannot_list_portfolios(): void
    {
        $response = $this->getJson('/api/list-portfolios');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_create_portfolio_form_config(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/get-create-portfolio-form-config');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $response->assertJsonPath('data.fields.0.name', 'portfolio_name');
        $response->assertJsonPath('data.fields.0.value', null);
        $response->assertJsonPath('data.fields.1.name', 'is_default');
        $response->assertJsonPath('data.fields.1.value', null);
    }

    public function test_authenticated_user_can_get_update_portfolio_form_config(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Income Portfolio',
            'is_default' => true,
        ]);

        $response = $this->getJson("/api/get-update-portfolio-form-config/{$portfolio->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $response->assertJsonPath('data.portfolio_id', $portfolio->id);
        $response->assertJsonPath('data.fields.0.name', 'portfolio_name');
        $response->assertJsonPath('data.fields.0.value', 'Income Portfolio');
        $response->assertJsonPath('data.fields.1.name', 'is_default');
        $response->assertJsonPath('data.fields.1.value', true);
    }

    public function test_user_cannot_get_update_form_config_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'portfolio_name' => 'Other User Portfolio',
        ]);

        $response = $this->getJson("/api/get-update-portfolio-form-config/{$portfolio->id}");

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
    }

    public function test_authenticated_user_can_create_portfolio(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/create-portfolio', [
            'portfolio_name' => 'Dividend Portfolio',
            'is_default' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        $response->assertJsonPath('data.portfolio_name', 'Dividend Portfolio');
        $response->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('portfolios', [
            'user_id' => $user->id,
            'portfolio_name' => 'Dividend Portfolio',
            'is_default' => true,
            'status_id' => 4,
        ]);
    }

    public function test_creating_default_portfolio_unsets_existing_default_portfolio(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $existingDefault = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Old Default',
            'is_default' => true,
        ]);

        $response = $this->postJson('/api/create-portfolio', [
            'portfolio_name' => 'New Default',
            'is_default' => true,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('portfolios', [
            'id' => $existingDefault->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('portfolios', [
            'portfolio_name' => 'New Default',
            'is_default' => true,
        ]);
    }

    public function test_create_portfolio_requires_portfolio_name(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/create-portfolio', [
            'is_default' => true,
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'portfolio_name',
        ]);
    }

    public function test_authenticated_user_can_update_portfolio(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Old Name',
            'is_default' => false,
        ]);

        $response = $this->putJson("/api/update-portfolio/{$portfolio->id}", [
            'portfolio_name' => 'Updated Name',
            'is_default' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $response->assertJsonPath('data.portfolio_name', 'Updated Name');
        $response->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
            'portfolio_name' => 'Updated Name',
            'is_default' => true,
        ]);
    }

    public function test_updating_default_portfolio_unsets_existing_default_portfolio(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $existingDefault = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Old Default',
            'is_default' => true,
        ]);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'New Default',
            'is_default' => false,
        ]);

        $response = $this->putJson("/api/update-portfolio/{$portfolio->id}", [
            'is_default' => true,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('portfolios', [
            'id' => $existingDefault->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
            'is_default' => true,
        ]);
    }

    public function test_user_cannot_update_another_users_portfolio(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'portfolio_name' => 'Other User Portfolio',
        ]);

        $response = $this->putJson("/api/update-portfolio/{$portfolio->id}", [
            'portfolio_name' => 'Bad Update',
        ]);

        $response->assertStatus(500);

        $this->assertDatabaseMissing('portfolios', [
            'id' => $portfolio->id,
            'portfolio_name' => 'Bad Update',
        ]);
    }

    public function test_authenticated_user_can_delete_portfolio_and_its_transactions(): void
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

        $response = $this->deleteJson("/api/delete-portfolio/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'message' => 'Portfolio deleted successfully.',
        ]);

        $this->assertDatabaseMissing('portfolios', [
            'id' => $portfolio->id,
        ]);

        $this->assertDatabaseMissing('portfolio_transactions', [
            'portfolio_id' => $portfolio->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_portfolio(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->deleteJson("/api/delete-portfolio/{$portfolio->id}");

        $response->assertStatus(500);

        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
        ]);
    }
}
