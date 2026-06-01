<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InternalTransfer;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $wallet = $this->resolveWallet($request->user());

        return response()->json($wallet);
    }

    public function transactions(Request $request): JsonResponse
    {
        $wallet = $this->resolveWallet($request->user());

        return response()->json($wallet->transactions()->latest()->paginate(25));
    }

    public function deposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $wallet = $this->resolveWallet($request->user());

        DB::transaction(function () use ($wallet, $validated): void {
            $wallet->increment('balance', $validated['amount']);
            $this->createTransaction($wallet, 'deposit', $validated['amount'], 'completed', $validated['reference'] ?? null);
        });

        return response()->json($wallet->fresh());
    }

    public function withdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $wallet = $this->resolveWallet($request->user());

        if ((float) $wallet->balance < (float) $validated['amount']) {
            throw ValidationException::withMessages([
                'amount' => ['Insufficient wallet balance.'],
            ]);
        }

        DB::transaction(function () use ($wallet, $validated): void {
            $wallet->decrement('balance', $validated['amount']);
            $this->createTransaction($wallet, 'withdrawal', $validated['amount'], 'completed', $validated['reference'] ?? null);
        });

        return response()->json($wallet->fresh());
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_email' => ['required', 'email', 'exists:users,email'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $sender = $request->user();
        $receiver = User::where('email', $validated['receiver_email'])->firstOrFail();

        if ($sender->id === $receiver->id) {
            throw ValidationException::withMessages([
                'receiver_email' => ['Receiver must be a different user.'],
            ]);
        }

        $senderWallet = $this->resolveWallet($sender);
        $receiverWallet = $this->resolveWallet($receiver);

        if ((float) $senderWallet->balance < (float) $validated['amount']) {
            throw ValidationException::withMessages([
                'amount' => ['Insufficient wallet balance.'],
            ]);
        }

        DB::transaction(function () use ($validated, $senderWallet, $receiverWallet, $sender, $receiver): void {
            $senderWallet->decrement('balance', $validated['amount']);
            $receiverWallet->increment('balance', $validated['amount']);

            InternalTransfer::create([
                'from_user_id' => $sender->id,
                'to_user_id' => $receiver->id,
                'amount' => $validated['amount'],
                'status' => 'completed',
                'note' => $validated['note'] ?? null,
            ]);

            $this->createTransaction($senderWallet, 'transfer_sent', $validated['amount'], 'completed', null, ['to_user_id' => $receiver->id]);
            $this->createTransaction($receiverWallet, 'transfer_received', $validated['amount'], 'completed', null, ['from_user_id' => $sender->id]);
        });

        return response()->json($senderWallet->fresh());
    }

    private function resolveWallet(User $user): Wallet
    {
        return $user->wallet()->firstOrCreate(
            ['currency' => 'USD'],
            ['balance' => 0]
        );
    }

    private function createTransaction(Wallet $wallet, string $type, float $amount, string $status, ?string $reference = null, ?array $meta = null): Transaction
    {
        return Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => $type,
            'amount' => $amount,
            'status' => $status,
            'reference' => $reference,
            'meta' => $meta,
        ]);
    }
}
