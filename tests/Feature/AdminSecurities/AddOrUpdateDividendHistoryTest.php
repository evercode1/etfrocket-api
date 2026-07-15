<?php

namespace Tests\Feature\AdminSecurities;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityDividendHistory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AddOrUpdateDividendHistoryTest extends TestCase
{
    private string $endpoint = '/api/admin/data/dividend-history';

    protected function setUp(): void
    {
        parent::setUp();

        SecurityDividendHistory::truncate();
        SecurityDetail::truncate();
        Security::truncate();
        User::truncate();
    }

    protected function tearDown(): void
    {
        SecurityDividendHistory::truncate();
        SecurityDetail::truncate();
        Security::truncate();
        User::truncate();

        parent::tearDown();
    }

    public function test_admin_user_can_add_a_dividend_history_record(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'nvii',
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0.2450,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'security_id' => $security->id,
                    'symbol' => 'NVII',
                    'ex_dividend_date' => '2026-07-10',
                    'payment_date' => '2026-07-14',
                    'previous_dividend_amount' => null,
                    'dividend_amount' => '0.2450',
                    'created' => true,
                    'changed' => true,
                ],
            ])
            ->assertJsonPath(
                'message',
                'NVII dividend history for 2026-07-10 was added with an amount of $0.2450.',
            );

        $this->assertDatabaseHas('security_dividend_histories', [
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0.2450,
        ]);
    }

    public function test_admin_user_can_update_an_existing_dividend_history_record(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
        ]);

        $dividendHistory = SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0.2450,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-15',
            'dividend_amount' => 0.2750,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'security_id' => $security->id,
                    'dividend_history_id' => $dividendHistory->id,
                    'symbol' => 'NVII',
                    'ex_dividend_date' => '2026-07-10',
                    'payment_date' => '2026-07-15',
                    'previous_dividend_amount' => '0.2450',
                    'dividend_amount' => '0.2750',
                    'created' => false,
                    'changed' => true,
                ],
            ])
            ->assertJsonPath(
                'message',
                'NVII dividend history for 2026-07-10 was updated from $0.2450 to $0.2750.',
            );

        $this->assertDatabaseHas('security_dividend_histories', [
            'id' => $dividendHistory->id,
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-15',
            'dividend_amount' => 0.2750,
        ]);

        $this->assertDatabaseCount('security_dividend_histories', 1);
    }

    public function test_admin_user_can_update_only_the_payment_date(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
        ]);

        $dividendHistory = SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0.2450,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-16',
            'dividend_amount' => 0.2450,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.created', false)
            ->assertJsonPath('data.changed', true)
            ->assertJsonPath('data.payment_date', '2026-07-16');

        $this->assertDatabaseHas('security_dividend_histories', [
            'id' => $dividendHistory->id,
            'payment_date' => '2026-07-16',
            'dividend_amount' => 0.2450,
        ]);
    }

    public function test_it_does_not_update_the_record_when_amount_and_payment_date_are_unchanged(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
        ]);

        $dividendHistory = SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0.2450,
        ]);

        $originalUpdatedAt = $dividendHistory->updated_at;

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0.2450,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'security_id' => $security->id,
                    'dividend_history_id' => $dividendHistory->id,
                    'symbol' => 'NVII',
                    'ex_dividend_date' => '2026-07-10',
                    'payment_date' => '2026-07-14',
                    'previous_dividend_amount' => '0.2450',
                    'dividend_amount' => '0.2450',
                    'created' => false,
                    'changed' => false,
                ],
            ]);

        $dividendHistory->refresh();

        $this->assertSame(
            $originalUpdatedAt?->format('Y-m-d H:i:s'),
            $dividendHistory->updated_at?->format('Y-m-d H:i:s'),
        );
    }

    public function test_non_admin_user_cannot_add_or_update_dividend_history(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0.2450,
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseMissing('security_dividend_histories', [
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-07-10',
        ]);
    }

    public function test_unauthenticated_user_cannot_add_or_update_dividend_history(): void
    {
        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0.2450,
        ]);

        $response->assertUnauthorized();
    }

    public function test_symbol_ex_dividend_date_payment_date_and_amount_are_required(): void
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
                'ex_dividend_date',
                'payment_date',
                'dividend_amount',
            ]);
    }

    public function test_dividend_amount_must_be_greater_than_zero(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dividend_amount');
    }

    public function test_dividend_dates_must_use_year_month_day_format(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'ex_dividend_date' => '07/10/2026',
            'payment_date' => '07/14/2026',
            'dividend_amount' => 0.2450,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ex_dividend_date',
                'payment_date',
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
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0.2450,
        ]);

        $response
            ->assertNotFound()
            ->assertJson([
                'status' => 'error',
                'message' => 'Security MISSING was not found.',
            ]);
    }

    public function test_dividend_amount_is_rounded_to_four_decimal_places(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson($this->endpoint, [
            'symbol' => 'NVII',
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0.24508,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.dividend_amount', '0.2451');

        $this->assertDatabaseHas('security_dividend_histories', [
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-07-10',
            'payment_date' => '2026-07-14',
            'dividend_amount' => 0.2451,
        ]);
    }
}
