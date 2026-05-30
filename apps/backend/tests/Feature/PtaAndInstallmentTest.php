<?php

namespace Tests\Feature;

use App\Models\InstallmentPlan;
use App\Models\PtaService;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PtaAndInstallmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_place_and_pay_pta_order_from_wallet(): void
    {
        $user = User::factory()->create();
        $service = PtaService::create([
            'name' => 'PTA Tax',
            'slug' => 'pta-tax',
            'base_fee' => 500,
            'is_active' => true,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'balance' => 40000,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $orderResponse = $this->withToken($token)->postJson('/api/pta/orders', [
            'pta_service_id' => $service->id,
            'imei_1' => '490154203237518',
            'sim_type' => 'single',
            'registration_type' => 'passport',
            'passport_number' => 'AB1234567',
        ]);

        $orderResponse->assertCreated();

        $orderId = $orderResponse->json('id');

        $this->withToken($token)
            ->postJson('/api/pta/orders/'.$orderId.'/pay', ['amount' => 18500])
            ->assertOk()
            ->assertJsonPath('status', 'in_review');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'pta_payment',
            'status' => 'completed',
        ]);
    }

    public function test_user_can_create_installment_contract_and_pay_installment(): void
    {
        $user = User::factory()->create();

        $plan = InstallmentPlan::create([
            'name' => 'iPhone Monthly Plan',
            'slug' => 'iphone-monthly',
            'device_price' => 1000,
            'down_payment' => 200,
            'installment_amount' => 100,
            'installment_count' => 8,
            'frequency' => 'monthly',
            'grace_period_days' => 5,
            'late_fee_type' => 'fixed',
            'late_fee_value' => 10,
            'is_active' => true,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'balance' => 1000,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $contractResponse = $this->withToken($token)
            ->postJson('/api/installments/plans/'.$plan->id.'/contracts', ['auto_deduction' => true])
            ->assertCreated();

        $contractId = $contractResponse->json('id');

        $this->withToken($token)
            ->postJson('/api/installments/contracts/'.$contractId.'/pay', ['amount' => 100])
            ->assertOk();

        $this->assertDatabaseHas('installment_payments', [
            'installment_contract_id' => $contractId,
            'payment_method' => 'wallet',
            'status' => 'completed',
        ]);
    }
}
