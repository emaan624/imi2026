<?php

namespace Tests\Feature;

use App\Models\InstallmentContract;
use App\Models\InstallmentPlan;
use App\Models\PtaOrder;
use App\Models\PtaService;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAndSupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users_but_non_admin_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $adminToken = $admin->createToken('admin')->plainTextToken;
        $memberToken = $member->createToken('member')->plainTextToken;

        $this->withToken($adminToken)
            ->getJson('/api/admin/users')
            ->assertOk();

        $this->withToken($memberToken)
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_user_can_create_and_view_own_ticket(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('member')->plainTextToken;

        $createResponse = $this->withToken($token)
            ->postJson('/api/tickets', [
                'subject' => 'Transfer issue',
                'message' => 'Internal transfer is pending.',
                'priority' => 'high',
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('status', 'open');

        $ticketId = $createResponse->json('id');

        $this->withToken($token)
            ->getJson('/api/tickets/'.$ticketId)
            ->assertOk()
            ->assertJsonPath('subject', 'Transfer issue');

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticketId,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
    }

    public function test_admin_can_list_pta_orders_and_installment_contracts(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $token = $admin->createToken('admin')->plainTextToken;

        $service = PtaService::create([
            'name' => 'PTA Tax',
            'slug' => 'pta-tax',
            'base_fee' => 500,
            'is_active' => true,
        ]);

        $order = PtaOrder::create([
            'user_id' => $member->id,
            'pta_service_id' => $service->id,
            'order_number' => 'PTA-TEST-0001',
            'imei_1' => '490154203237518',
            'sim_type' => 'single',
            'registration_type' => 'passport',
            'passport_number' => 'AB1234567',
            'status' => 'pending_payment',
            'tax_amount' => 18000,
            'service_fee' => 500,
            'total_amount' => 18500,
            'paid_amount' => 0,
            'wallet_payment_amount' => 0,
        ]);

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

        $contract = InstallmentContract::create([
            'user_id' => $member->id,
            'installment_plan_id' => $plan->id,
            'contract_number' => 'INS-TEST-0001',
            'status' => 'pending_approval',
            'verification_status' => 'pending',
            'total_amount' => 1000,
            'down_payment' => 200,
            'paid_amount' => 200,
            'remaining_balance' => 800,
            'auto_deduction' => false,
            'risk_score' => 20,
        ]);

        $this->withToken($token)
            ->getJson('/api/admin/pta/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id);

        $this->withToken($token)
            ->getJson('/api/admin/pta/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('id', $order->id);

        $this->withToken($token)
            ->getJson('/api/admin/installments/contracts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $contract->id);

        $this->withToken($token)
            ->getJson('/api/admin/installments/contracts/'.$contract->id)
            ->assertOk()
            ->assertJsonPath('id', $contract->id);
    }
}
