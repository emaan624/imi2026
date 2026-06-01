<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_get_wallet(): void
    {
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Phase One User',
            'email' => 'phase1@example.com',
            'password' => 'password123',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'email', 'role'], 'token']);

        $token = $registerResponse->json('token');

        $this->withToken($token)
            ->getJson('/api/wallet')
            ->assertOk()
            ->assertJsonPath('currency', 'USD')
            ->assertJsonPath('balance', '0.00');
    }

    public function test_user_can_deposit_and_transfer_funds(): void
    {
        $sender = User::factory()->create(['email' => 'sender@example.com']);
        $receiver = User::factory()->create(['email' => 'receiver@example.com']);

        Wallet::create(['user_id' => $sender->id, 'currency' => 'USD', 'balance' => 0]);
        Wallet::create(['user_id' => $receiver->id, 'currency' => 'USD', 'balance' => 0]);

        $token = $sender->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/wallet/deposit', ['amount' => 200])
            ->assertOk()
            ->assertJsonPath('balance', '200.00');

        $this->withToken($token)
            ->postJson('/api/wallet/transfer', [
                'receiver_email' => $receiver->email,
                'amount' => 50,
            ])
            ->assertOk()
            ->assertJsonPath('balance', '150.00');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $receiver->id,
            'balance' => 50.00,
        ]);
    }
}
