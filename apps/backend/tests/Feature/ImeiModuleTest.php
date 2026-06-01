<?php

namespace Tests\Feature;

use App\Models\ImeiCategory;
use App\Models\ImeiProvider;
use App\Models\ImeiService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImeiModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_place_imei_order(): void
    {
        $user = User::factory()->create();

        $category = ImeiCategory::create([
            'name' => 'IMEI Checkers',
            'slug' => 'imei-checkers',
            'is_active' => true,
        ]);

        $service = ImeiService::create([
            'imei_category_id' => $category->id,
            'name' => 'FMI Checker',
            'slug' => 'fmi-checker',
            'checker_type' => 'fmi',
            'price' => 3.99,
            'is_active' => true,
        ]);

        ImeiProvider::create([
            'name' => 'Fallback Provider',
            'slug' => 'fallback-provider',
            'type' => 'generic_rest',
            'priority' => 1,
            'api_url' => 'http://localhost',
            'is_active' => true,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/imei/orders', [
            'imei_service_id' => $service->id,
            'imei' => '490154203237518',
        ])->assertCreated()->assertJsonPath('status', 'pending_submission');
    }

    public function test_admin_can_access_imei_analytics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/imei/analytics')
            ->assertOk()
            ->assertJsonStructure([
                'profit_analytics',
                'revenue_analytics',
                'api_statistics',
                'failed_order_management',
                'refund_management',
                'provider_balance_dashboard',
            ]);
    }
}
