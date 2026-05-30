<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstallmentContract;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Models\InstallmentReminder;
use App\Models\InstallmentSchedule;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstallmentController extends Controller
{
    public function plans(): JsonResponse
    {
        return response()->json(InstallmentPlan::where('is_active', true)->latest()->get());
    }

    public function createContract(Request $request, InstallmentPlan $installmentPlan): JsonResponse
    {
        $validated = $request->validate([
            'auto_deduction' => ['sometimes', 'boolean'],
            'risk_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'verification_status' => ['sometimes', 'in:pending,verified,rejected'],
        ]);

        $user = $request->user();
        $wallet = $this->resolveWallet($user);

        if ((float) $wallet->balance < (float) $installmentPlan->down_payment) {
            throw ValidationException::withMessages([
                'down_payment' => ['Insufficient wallet balance for down payment.'],
            ]);
        }

        $contract = DB::transaction(function () use ($validated, $installmentPlan, $user, $wallet): InstallmentContract {
            $wallet->decrement('balance', $installmentPlan->down_payment);

            $contract = InstallmentContract::create([
                'user_id' => $user->id,
                'installment_plan_id' => $installmentPlan->id,
                'contract_number' => 'INS-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
                'status' => 'pending_approval',
                'verification_status' => $validated['verification_status'] ?? 'pending',
                'total_amount' => $installmentPlan->device_price,
                'down_payment' => $installmentPlan->down_payment,
                'paid_amount' => $installmentPlan->down_payment,
                'remaining_balance' => max((float) $installmentPlan->device_price - (float) $installmentPlan->down_payment, 0),
                'auto_deduction' => $validated['auto_deduction'] ?? false,
                'risk_score' => $validated['risk_score'] ?? $installmentPlan->risk_level * 10,
                'meta' => [
                    'installment_frequency' => $installmentPlan->frequency,
                    'customer_verification' => 'submitted',
                ],
            ]);

            Transaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => 'installment_down_payment',
                'amount' => $installmentPlan->down_payment,
                'status' => 'completed',
                'reference' => $contract->contract_number,
                'meta' => [
                    'contract_id' => $contract->id,
                    'plan_id' => $installmentPlan->id,
                ],
            ]);

            $this->generateSchedule($contract, $installmentPlan);

            return $contract;
        });

        return response()->json($contract->load(['plan', 'schedules']), 201);
    }

    public function contracts(Request $request): JsonResponse
    {
        $contracts = InstallmentContract::with(['plan'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($contracts);
    }

    public function show(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $this->authorizeContract($installmentContract, $request->user());

        return response()->json($installmentContract->load(['plan', 'schedules', 'payments']));
    }

    public function schedule(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $this->authorizeContract($installmentContract, $request->user());

        return response()->json($installmentContract->schedules()->orderBy('installment_no')->get());
    }

    public function history(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $this->authorizeContract($installmentContract, $request->user());

        return response()->json($installmentContract->payments()->latest()->get());
    }

    public function remainingBalance(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $this->authorizeContract($installmentContract, $request->user());

        return response()->json([
            'contract_number' => $installmentContract->contract_number,
            'total_amount' => $installmentContract->total_amount,
            'paid_amount' => $installmentContract->paid_amount,
            'remaining_balance' => $installmentContract->remaining_balance,
            'next_due_date' => $installmentContract->next_due_date,
        ]);
    }

    public function statement(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $this->authorizeContract($installmentContract, $request->user());

        return response()->json([
            'contract' => $installmentContract->load('plan'),
            'payments' => $installmentContract->payments()->latest()->get(),
            'schedules' => $installmentContract->schedules()->orderBy('installment_no')->get(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function agreement(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $this->authorizeContract($installmentContract, $request->user());

        return response()->json([
            'contract_number' => $installmentContract->contract_number,
            'agreement_title' => 'Installment Agreement',
            'pdf_url' => '/api/installments/contracts/'.$installmentContract->id.'/agreement',
            'summary' => [
                'total_amount' => $installmentContract->total_amount,
                'remaining_balance' => $installmentContract->remaining_balance,
                'frequency' => $installmentContract->plan?->frequency,
            ],
        ]);
    }

    public function pay(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $this->authorizeContract($installmentContract, $request->user());

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'reference' => ['nullable', 'string', 'max:255'],
            'schedule_id' => ['nullable', 'exists:installment_schedules,id'],
        ]);

        if ((float) $installmentContract->remaining_balance <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Installment contract is already settled.'],
            ]);
        }

        $wallet = $this->resolveWallet($request->user());
        $remaining = (float) $installmentContract->remaining_balance;
        $paymentAmount = round(min($remaining, (float) $validated['amount']), 2);

        if ((float) $wallet->balance < $paymentAmount) {
            throw ValidationException::withMessages([
                'amount' => ['Insufficient wallet balance.'],
            ]);
        }

        DB::transaction(function () use ($installmentContract, $wallet, $paymentAmount, $validated): void {
            $schedule = isset($validated['schedule_id'])
                ? InstallmentSchedule::where('installment_contract_id', $installmentContract->id)->find($validated['schedule_id'])
                : $installmentContract->schedules()->where('status', 'pending')->orderBy('installment_no')->first();

            $lateFee = 0.0;
            if ($schedule && now()->toDateString() > $schedule->grace_until?->toDateString()) {
                $lateFee = (float) $schedule->late_fee_amount;
            }

            $principal = max($paymentAmount - $lateFee, 0);

            $wallet->decrement('balance', $paymentAmount);

            $installmentContract->increment('paid_amount', $paymentAmount);
            $installmentContract->decrement('remaining_balance', $paymentAmount);

            $freshContract = $installmentContract->fresh();
            $freshContract->status = (float) $freshContract->remaining_balance <= 0 ? 'settled' : ($freshContract->status === 'pending_approval' ? 'active' : $freshContract->status);
            $freshContract->next_due_date = $freshContract->schedules()->where('status', 'pending')->orderBy('due_date')->value('due_date');
            $freshContract->save();

            if ($schedule && $paymentAmount >= (float) $schedule->amount) {
                $schedule->status = 'paid';
                $schedule->paid_at = now();
                $schedule->save();
            }

            InstallmentPayment::create([
                'installment_contract_id' => $freshContract->id,
                'installment_schedule_id' => $schedule?->id,
                'wallet_id' => $wallet->id,
                'amount' => $paymentAmount,
                'principal_amount' => $principal,
                'late_fee_amount' => $lateFee,
                'payment_method' => 'wallet',
                'status' => 'completed',
                'reference' => $validated['reference'] ?? $freshContract->contract_number,
                'paid_at' => now(),
                'meta' => [
                    'partial_payment' => (float) $paymentAmount < (float) ($schedule?->amount ?? 0),
                ],
            ]);

            Transaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => 'installment_payment',
                'amount' => $paymentAmount,
                'status' => 'completed',
                'reference' => $validated['reference'] ?? $freshContract->contract_number,
                'meta' => [
                    'contract_id' => $freshContract->id,
                    'schedule_id' => $schedule?->id,
                ],
            ]);

            InstallmentReminder::create([
                'installment_contract_id' => $freshContract->id,
                'installment_schedule_id' => $schedule?->id,
                'type' => 'payment_confirmation',
                'channel' => 'push',
                'status' => 'sent',
                'scheduled_at' => now(),
                'sent_at' => now(),
                'meta' => ['amount' => $paymentAmount],
            ]);
        });

        return response()->json($installmentContract->fresh()->load(['schedules', 'payments']));
    }

    public function settleEarly(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $this->authorizeContract($installmentContract, $request->user());

        $request->merge(['amount' => $installmentContract->remaining_balance]);

        return $this->pay($request, $installmentContract);
    }

    private function generateSchedule(InstallmentContract $contract, InstallmentPlan $plan): void
    {
        $startDate = now()->addDay();
        $currentDue = $startDate->copy();

        for ($i = 1; $i <= $plan->installment_count; $i++) {
            if ($i > 1) {
                $currentDue = match ($plan->frequency) {
                    'weekly' => $currentDue->addWeek(),
                    'monthly' => $currentDue->addMonth(),
                    default => $currentDue->addDays(10),
                };
            }

            $lateFee = $plan->late_fee_type === 'percentage'
                ? ((float) $plan->installment_amount * (float) $plan->late_fee_value) / 100
                : (float) $plan->late_fee_value;

            InstallmentSchedule::create([
                'installment_contract_id' => $contract->id,
                'installment_no' => $i,
                'due_date' => $currentDue->toDateString(),
                'grace_until' => $currentDue->copy()->addDays($plan->grace_period_days)->toDateString(),
                'amount' => $plan->installment_amount,
                'late_fee_amount' => round($lateFee, 2),
                'status' => 'pending',
            ]);
        }

        $contract->start_date = $startDate->toDateString();
        $contract->end_date = $currentDue->toDateString();
        $contract->next_due_date = $startDate->toDateString();
        $contract->save();
    }

    private function authorizeContract(InstallmentContract $contract, User $user): void
    {
        if ($user->role !== 'admin' && $contract->user_id !== $user->id) {
            abort(403);
        }
    }

    private function resolveWallet(User $user): Wallet
    {
        return $user->wallet()->firstOrCreate(
            ['currency' => 'USD'],
            ['balance' => 0]
        );
    }
}
