<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstallmentContract;
use App\Models\InstallmentPlan;
use App\Models\InstallmentReminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInstallmentController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'plans_total' => InstallmentPlan::count(),
            'contracts_total' => InstallmentContract::count(),
            'contracts_pending_approval' => InstallmentContract::where('status', 'pending_approval')->count(),
            'defaulters' => InstallmentContract::where('status', 'defaulted')->count(),
            'collection_total' => InstallmentContract::sum('paid_amount'),
            'remaining_balance_total' => InstallmentContract::sum('remaining_balance'),
        ]);
    }

    public function analytics(): JsonResponse
    {
        return response()->json([
            'status_breakdown' => InstallmentContract::selectRaw('status, COUNT(*) as total')->groupBy('status')->get(),
            'frequency_breakdown' => InstallmentPlan::selectRaw('frequency, COUNT(*) as total')->groupBy('frequency')->get(),
            'risk_distribution' => InstallmentContract::selectRaw('risk_score, COUNT(*) as total')->groupBy('risk_score')->orderBy('risk_score')->get(),
        ]);
    }

    public function contractsIndex(): JsonResponse
    {
        return response()->json(
            InstallmentContract::with(['user:id,name,email', 'plan:id,name,slug'])
                ->latest()
                ->paginate(20)
        );
    }

    public function contractShow(InstallmentContract $installmentContract): JsonResponse
    {
        return response()->json(
            $installmentContract->load([
                'user:id,name,email',
                'plan:id,name,slug',
                'payments',
                'schedules',
                'reminders',
            ])
        );
    }

    public function plansIndex(): JsonResponse
    {
        return response()->json(InstallmentPlan::latest()->paginate(20));
    }

    public function plansStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:installment_plans,slug'],
            'description' => ['nullable', 'string'],
            'device_price' => ['required', 'numeric', 'min:1'],
            'down_payment' => ['required', 'numeric', 'min:0'],
            'installment_amount' => ['required', 'numeric', 'min:1'],
            'installment_count' => ['required', 'integer', 'min:1'],
            'frequency' => ['required', 'in:weekly,monthly,custom'],
            'grace_period_days' => ['required', 'integer', 'min:0'],
            'late_fee_type' => ['required', 'in:fixed,percentage'],
            'late_fee_value' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'risk_level' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        $plan = InstallmentPlan::create($validated);

        return response()->json($plan, 201);
    }

    public function plansUpdate(Request $request, InstallmentPlan $installmentPlan): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:installment_plans,slug,'.$installmentPlan->id],
            'description' => ['nullable', 'string'],
            'device_price' => ['sometimes', 'numeric', 'min:1'],
            'down_payment' => ['sometimes', 'numeric', 'min:0'],
            'installment_amount' => ['sometimes', 'numeric', 'min:1'],
            'installment_count' => ['sometimes', 'integer', 'min:1'],
            'frequency' => ['sometimes', 'in:weekly,monthly,custom'],
            'grace_period_days' => ['sometimes', 'integer', 'min:0'],
            'late_fee_type' => ['sometimes', 'in:fixed,percentage'],
            'late_fee_value' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'risk_level' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        $installmentPlan->update($validated);

        return response()->json($installmentPlan->fresh());
    }

    public function plansDestroy(InstallmentPlan $installmentPlan): JsonResponse
    {
        $installmentPlan->delete();

        return response()->json(['message' => 'Plan deleted']);
    }

    public function approve(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $validated = $request->validate([
            'verification_status' => ['sometimes', 'in:verified,rejected'],
        ]);

        $installmentContract->status = 'active';
        $installmentContract->approved_by = $request->user()->id;
        $installmentContract->approved_at = now();
        $installmentContract->verification_status = $validated['verification_status'] ?? 'verified';
        $installmentContract->save();

        return response()->json($installmentContract->fresh());
    }

    public function markDefaulter(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $meta = $installmentContract->meta ?? [];
        $meta['defaulter_reason'] = $validated['reason'] ?? 'Payment overdue';

        $installmentContract->update([
            'status' => 'defaulted',
            'meta' => $meta,
        ]);

        return response()->json($installmentContract->fresh());
    }

    public function recoveryNote(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        $meta = $installmentContract->meta ?? [];
        $notes = $meta['recovery_notes'] ?? [];
        $notes[] = ['note' => $validated['note'], 'created_at' => now()->toIso8601String()];
        $meta['recovery_notes'] = $notes;

        $installmentContract->update(['meta' => $meta]);

        return response()->json($installmentContract->fresh());
    }

    public function adjust(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $validated = $request->validate([
            'remaining_balance' => ['nullable', 'numeric', 'min:0'],
            'risk_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if (isset($validated['remaining_balance'])) {
            $installmentContract->remaining_balance = $validated['remaining_balance'];
        }

        if (isset($validated['risk_score'])) {
            $installmentContract->risk_score = $validated['risk_score'];
        }

        $meta = $installmentContract->meta ?? [];
        if (! empty($validated['note'])) {
            $adjustments = $meta['manual_adjustments'] ?? [];
            $adjustments[] = [
                'note' => $validated['note'],
                'created_at' => now()->toIso8601String(),
            ];
            $meta['manual_adjustments'] = $adjustments;
            $installmentContract->meta = $meta;
        }

        $installmentContract->save();

        return response()->json($installmentContract->fresh());
    }

    public function triggerReminder(Request $request, InstallmentContract $installmentContract): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:upcoming,overdue,payment_confirmation'],
            'channel' => ['required', 'in:email,sms,push'],
        ]);

        $reminder = InstallmentReminder::create([
            'installment_contract_id' => $installmentContract->id,
            'type' => $validated['type'],
            'channel' => $validated['channel'],
            'status' => 'sent',
            'scheduled_at' => now(),
            'sent_at' => now(),
        ]);

        return response()->json($reminder, 201);
    }
}
