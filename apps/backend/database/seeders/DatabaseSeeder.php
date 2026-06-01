<?php

namespace Database\Seeders;

use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Wallet;
use App\Models\ImeiCategory;
use App\Models\ImeiProvider;
use App\Models\ImeiService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@imi.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@imi.local',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        foreach ([$admin, $user] as $seedUser) {
            $wallet = Wallet::create([
                'user_id' => $seedUser->id,
                'currency' => 'USD',
                'balance' => 1000,
            ]);

            Transaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $seedUser->id,
                'type' => 'deposit',
                'amount' => 1000,
                'status' => 'completed',
                'reference' => 'seed-initial-fund',
            ]);

            UserNotification::create([
                'user_id' => $seedUser->id,
                'title' => 'Welcome to IMI Platform',
                'message' => 'Your Phase 1 account is ready.',
                'is_read' => false,
            ]);

            SupportTicket::create([
                'user_id' => $seedUser->id,
                'subject' => 'Getting started',
                'message' => 'Please help me understand wallet transfers.',
                'status' => 'open',
                'priority' => 'medium',
            ]);
        }

        $checkerCategory = ImeiCategory::updateOrCreate(
            ['slug' => 'imei-checkers'],
            [
                'name' => 'IMEI Checkers',
                'description' => 'IMEI diagnostic and verification services',
                'is_active' => true,
            ]
        );

        foreach ([
            ['name' => 'FMI Checker', 'slug' => 'fmi-checker', 'checker_type' => 'fmi'],
            ['name' => 'Carrier Checker', 'slug' => 'carrier-checker', 'checker_type' => 'carrier'],
            ['name' => 'Blacklist Checker', 'slug' => 'blacklist-checker', 'checker_type' => 'blacklist'],
            ['name' => 'Warranty Checker', 'slug' => 'warranty-checker', 'checker_type' => 'warranty'],
            ['name' => 'Network Checker', 'slug' => 'network-checker', 'checker_type' => 'network'],
            ['name' => 'Device Info Checker', 'slug' => 'device-info-checker', 'checker_type' => 'device_info'],
        ] as $serviceSeed) {
            ImeiService::updateOrCreate(
                ['slug' => $serviceSeed['slug']],
                [
                    'imei_category_id' => $checkerCategory->id,
                    'name' => $serviceSeed['name'],
                    'checker_type' => $serviceSeed['checker_type'],
                    'price' => 3.99,
                    'estimated_time_minutes' => 5,
                    'is_active' => true,
                ]
            );
        }

        ImeiProvider::updateOrCreate(
            ['slug' => 'unlockbase-main'],
            [
                'name' => 'UnlockBase',
                'type' => 'unlockbase',
                'priority' => 1,
                'api_url' => 'https://api.unlockbase.example',
                'api_key' => 'demo-key',
                'is_active' => true,
                'supports_webhooks' => true,
            ]
        );

        ImeiProvider::updateOrCreate(
            ['slug' => 'dhru-fusion-main'],
            [
                'name' => 'Dhru Fusion',
                'type' => 'dhru_fusion',
                'priority' => 2,
                'api_url' => 'https://api.dhrufusion.example',
                'api_key' => 'demo-key',
                'username' => 'demo',
                'password' => 'demo',
                'is_active' => true,
                'supports_webhooks' => true,
            ]
        );
    }
}
