<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PtaOrder;
use App\Models\PtaOrderLog;
use App\Models\PtaService;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PtaController extends Controller
{
    public function services(): JsonResponse
    {
        return response()->json(PtaService::where('is_active', true)->latest()->get());
    }

    public function calculator(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'imei_1' => ['required', 'digits:15'],
            'imei_2' => ['nullable', 'digits:15'],
            'sim_type' => ['required', 'in:single,dual'],
            'pta_service_id' => ['nullable', 'exists:pta_services,id'],
        ]);

        $service = isset($validated['pta_service_id'])
            ? PtaService::find($validated['pta_service_id'])
            : PtaService::query()->where('slug', 'pta-tax')->first();

        $taxAmount = $validated['sim_type'] === 'dual' ? 24000 : 18000;
        $serviceFee = (float) ($service?->base_fee ?? 500);

        return response()->json([
            'tax_amount' => round($taxAmount, 2),
            'service_fee' => round($serviceFee, 2),
            'total_amount' => round($taxAmount + $serviceFee, 2),
            'sim_type' => $validated['sim_type'],
            'service' => $service,
        ]);
    }

    public function validateImei(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'imei' => ['required', 'digits:15'],
        ]);

        return response()->json([
            'imei' => $validated['imei'],
            'is_valid' => $this->passesLuhn($validated['imei']),
            'message' => $this->passesLuhn($validated['imei']) ? 'Valid IMEI.' : 'Invalid IMEI.',
        ]);
    }

    public function eligibility(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'imei' => ['required', 'digits:15'],
        ]);

        $alreadyApproved = PtaOrder::where('imei_1', $validated['imei'])
            ->orWhere('imei_2', $validated['imei'])
            ->where('status', 'approved')
            ->exists();

        return response()->json([
            'imei' => $validated['imei'],
            'eligible' => ! $alreadyApproved,
            'reason' => $alreadyApproved ? 'Device already has approved PTA.' : 'Device eligible for registration.',
        ]);
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pta_service_id' => ['required', 'exists:pta_services,id'],
            'imei_1' => ['required', 'digits:15'],
            'imei_2' => ['nullable', 'digits:15'],
            'sim_type' => ['required', 'in:single,dual'],
            'registration_type' => ['required', 'in:passport,cnic,overseas'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'cnic_number' => ['nullable', 'string', 'max:20'],
        ]);

        $service = PtaService::findOrFail($validated['pta_service_id']);
        $taxAmount = $validated['sim_type'] === 'dual' ? 24000 : 18000;
        $serviceFee = (float) $service->base_fee;
        $totalAmount = $taxAmount + $serviceFee;

        $order = DB::transaction(function () use ($request, $validated, $service, $taxAmount, $serviceFee, $totalAmount): PtaOrder {
            $order = PtaOrder::create([
                ...$validated,
                'user_id' => $request->user()->id,
                'order_number' => 'PTA-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
                'status' => 'pending_payment',
                'tax_amount' => $taxAmount,
                'service_fee' => $serviceFee,
                'total_amount' => $totalAmount,
            ]);

            $this->log($order, $request->user(), 'order_placed', null, $order->status, 'PTA order created', [
                'service' => $service->slug,
            ]);

            return $order;
        });

        return response()->json($order->load('service'), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = PtaOrder::with('service')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Request $request, PtaOrder $ptaOrder): JsonResponse
    {
        $this->authorizeOrder($ptaOrder, $request->user());

        return response()->json($ptaOrder->load(['service', 'logs']));
    }

    public function status(Request $request, PtaOrder $ptaOrder): JsonResponse
    {
        $this->authorizeOrder($ptaOrder, $request->user());

        return response()->json([
            'order_number' => $ptaOrder->order_number,
            'status' => $ptaOrder->status,
            'approved_at' => $ptaOrder->approved_at,
            'paid_amount' => $ptaOrder->paid_amount,
            'total_amount' => $ptaOrder->total_amount,
        ]);
    }

    public function history(Request $request, PtaOrder $ptaOrder): JsonResponse
    {
        $this->authorizeOrder($ptaOrder, $request->user());

        return response()->json($ptaOrder->logs()->with('user:id,name,email')->latest()->get());
    }

    public function pay(Request $request, PtaOrder $ptaOrder): JsonResponse
    {
        $this->authorizeOrder($ptaOrder, $request->user());

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $remaining = round((float) $ptaOrder->total_amount - (float) $ptaOrder->paid_amount, 2);
        $payable = round(min($remaining, (float) $validated['amount']), 2);

        if ($payable <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['PTA order is already fully paid.'],
            ]);
        }

        $wallet = $this->resolveWallet($request->user());

        if ((float) $wallet->balance < $payable) {
            throw ValidationException::withMessages([
                'amount' => ['Insufficient wallet balance.'],
            ]);
        }

        DB::transaction(function () use ($ptaOrder, $wallet, $payable, $validated, $request): void {
            $from = $ptaOrder->status;

            $wallet->decrement('balance', $payable);
            $ptaOrder->increment('paid_amount', $payable);
            $ptaOrder->increment('wallet_payment_amount', $payable);

            $freshOrder = $ptaOrder->fresh();
            $freshOrder->status = (float) $freshOrder->paid_amount >= (float) $freshOrder->total_amount ? 'in_review' : 'pending_payment';
            $freshOrder->save();

            Transaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => 'pta_payment',
                'amount' => $payable,
                'status' => 'completed',
                'reference' => $validated['reference'] ?? $freshOrder->order_number,
                'meta' => [
                    'pta_order_id' => $freshOrder->id,
                    'partial_payment' => (float) $freshOrder->paid_amount < (float) $freshOrder->total_amount,
                ],
            ]);

            $this->log($freshOrder, $request->user(), 'wallet_payment', $from, $freshOrder->status, 'PTA payment received from wallet', [
                'amount' => $payable,
            ]);
        });

        return response()->json($ptaOrder->fresh());
    }

    public function invoice(Request $request, PtaOrder $ptaOrder): JsonResponse
    {
        $this->authorizeOrder($ptaOrder, $request->user());

        return response()->json([
            'invoice_no' => 'INV-'.$ptaOrder->order_number,
            'order_number' => $ptaOrder->order_number,
            'tax_amount' => $ptaOrder->tax_amount,
            'service_fee' => $ptaOrder->service_fee,
            'total_amount' => $ptaOrder->total_amount,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function receipt(Request $request, PtaOrder $ptaOrder): JsonResponse
    {
        $this->authorizeOrder($ptaOrder, $request->user());

        return response()->json([
            'receipt_no' => 'RCPT-'.$ptaOrder->order_number,
            'order_number' => $ptaOrder->order_number,
            'paid_amount' => $ptaOrder->paid_amount,
            'wallet_payment_amount' => $ptaOrder->wallet_payment_amount,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function registerByPassport(Request $request, PtaOrder $ptaOrder): JsonResponse
    {
        $validated = $request->validate([
            'passport_number' => ['required', 'string', 'max:50'],
        ]);

        return $this->applyRegistrationType($request, $ptaOrder, 'passport', $validated['passport_number']);
    }

    public function registerByCnic(Request $request, PtaOrder $ptaOrder): JsonResponse
    {
        $validated = $request->validate([
            'cnic_number' => ['required', 'string', 'max:20'],
        ]);

        return $this->applyRegistrationType($request, $ptaOrder, 'cnic', $validated['cnic_number']);
    }

    public function registerOverseas(Request $request, PtaOrder $ptaOrder): JsonResponse
    {
        $validated = $request->validate([
            'passport_number' => ['required', 'string', 'max:50'],
        ]);

        return $this->applyRegistrationType($request, $ptaOrder, 'overseas', $validated['passport_number']);
    }

    private function applyRegistrationType(Request $request, PtaOrder $ptaOrder, string $type, string $number): JsonResponse
    {
        $this->authorizeOrder($ptaOrder, $request->user());

        $from = $ptaOrder->status;

        $ptaOrder->update([
            'registration_type' => $type,
            'passport_number' => in_array($type, ['passport', 'overseas'], true) ? $number : $ptaOrder->passport_number,
            'cnic_number' => $type === 'cnic' ? $number : $ptaOrder->cnic_number,
            'status' => (float) $ptaOrder->paid_amount >= (float) $ptaOrder->total_amount ? 'in_review' : $ptaOrder->status,
        ]);

        $this->log($ptaOrder->fresh(), $request->user(), 'registration_update', $from, $ptaOrder->status, 'Registration details updated');

        return response()->json($ptaOrder->fresh());
    }

    private function authorizeOrder(PtaOrder $ptaOrder, User $user): void
    {
        if ($user->role !== 'admin' && $ptaOrder->user_id !== $user->id) {
            abort(403);
        }
    }

    private function log(PtaOrder $order, ?User $user, string $action, ?string $fromStatus = null, ?string $toStatus = null, ?string $notes = null, ?array $meta = null): void
    {
        PtaOrderLog::create([
            'pta_order_id' => $order->id,
            'user_id' => $user?->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'notes' => $notes,
            'meta' => $meta,
        ]);
    }

    private function resolveWallet(User $user): Wallet
    {
        return $user->wallet()->firstOrCreate(
            ['currency' => 'USD'],
            ['balance' => 0]
        );
    }

    private function passesLuhn(string $value): bool
    {
        $sum = 0;
        $alt = false;

        for ($i = strlen($value) - 1; $i >= 0; $i--) {
            $n = (int) $value[$i];

            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }

            $sum += $n;
            $alt = ! $alt;
        }

        return $sum % 10 === 0;
    }
}
