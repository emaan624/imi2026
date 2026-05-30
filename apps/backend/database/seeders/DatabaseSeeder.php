<?php

namespace Database\Seeders;

use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Wallet;
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
    }
}
