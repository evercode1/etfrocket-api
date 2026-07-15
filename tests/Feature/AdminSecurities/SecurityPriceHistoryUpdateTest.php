<?php

namespace Tests\Feature\Admin\Securities;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityPriceHistory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityPriceHistoryUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SecurityPriceHistory::truncate();
        SecurityDetail::truncate();
        Security::truncate();
        User::truncate();
    }

    protected function tearDown(): void
    {
        SecurityPriceHistory::truncate();
        SecurityDetail::truncate();
        Security::truncate();
        User::truncate();

        parent::tearDown();
    }

    private string $endpoint = '/api/admin/data/price-history';

    public function test_admin_user_can_update_a_security_price_history_record(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
        ]);

        $priceHistory = SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-07-10',
            'close_price' => 18.4250,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'nvii',
            'price_date' => '2026-07-10',
            'close_price' => 19.1750,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'security_id' => $security->id,
                    'price_history_id' => $priceHistory->id,
                    'symbol' => 'NVII',
                    'price_date' => '2026-07-10',
                    'previous_close_price' => '18.4250',
                    'close_price' => '19.1750',
                    'changed' => true,
                ],
            ])
            ->assertJsonPath(
                'message',
                'NVII closing price for 2026-07-10 was updated from $18.4250 to $19.1750.',
            );

        $this->assertDatabaseHas('security_price_histories', [
            'id' => $priceHistory->id,
            'security_id' => $security->id,
            'price_date' => '2026-07-10',
            'close_price' => 19.1750,
        ]);
    }

    public function test_non_admin_user_cannot_update_a_security_price_history_record(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
        ]);

        $priceHistory = SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-07-10',
            'close_price' => 18.4250,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'price_date' => '2026-07-10',
            'close_price' => 19.1750,
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseHas('security_price_histories', [
            'id' => $priceHistory->id,
            'close_price' => 18.4250,
        ]);
    }

    public function test_unauthenticated_user_cannot_update_a_security_price_history_record(): void
    {
        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'price_date' => '2026-07-10',
            'close_price' => 19.1750,
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_requires_symbol_price_date_and_close_price(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'symbol',
                'price_date',
                'close_price',
            ]);
    }

    public function test_close_price_must_be_greater_than_zero(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'price_date' => '2026-07-10',
            'close_price' => 0,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'close_price',
            ]);
    }

    public function test_price_date_must_use_year_month_day_format(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'price_date' => '07/10/2026',
            'close_price' => 19.1750,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'price_date',
            ]);
    }

    public function test_it_returns_not_found_when_the_security_does_not_exist(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'MISSING',
            'price_date' => '2026-07-10',
            'close_price' => 19.1750,
        ]);

        $response
            ->assertNotFound()
            ->assertJson([
                'status' => 'error',
                'message' => 'Security MISSING was not found.',
            ]);
    }

    public function test_it_returns_not_found_when_price_history_does_not_exist_for_the_date(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Security::factory()->create([
            'symbol' => 'NVII',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'price_date' => '2026-07-10',
            'close_price' => 19.1750,
        ]);

        $response
            ->assertNotFound()
            ->assertJson([
                'status' => 'error',
                'message' => 'No price history record was found for NVII on 2026-07-10.',
            ]);
    }

    public function test_it_does_not_update_the_record_when_the_price_is_unchanged(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
        ]);

        $priceHistory = SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-07-10',
            'close_price' => 18.4250,
        ]);

        $originalUpdatedAt = $priceHistory->updated_at;

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'price_date' => '2026-07-10',
            'close_price' => 18.4250,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'security_id' => $security->id,
                    'symbol' => 'NVII',
                    'price_date' => '2026-07-10',
                    'previous_close_price' => '18.4250',
                    'close_price' => '18.4250',
                    'changed' => false,
                ],
            ]);

        $priceHistory->refresh();

        $this->assertSame(
            $originalUpdatedAt?->format('Y-m-d H:i:s'),
            $priceHistory->updated_at?->format('Y-m-d H:i:s'),
        );
    }

    public function test_close_price_is_rounded_to_four_decimal_places(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
        ]);

        $priceHistory = SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-07-10',
            'close_price' => 18.4250,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'price_date' => '2026-07-10',
            'close_price' => 19.17508,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.close_price', '19.1751');

        $this->assertDatabaseHas('security_price_histories', [
            'id' => $priceHistory->id,
            'close_price' => 19.1751,
        ]);
    }
}
