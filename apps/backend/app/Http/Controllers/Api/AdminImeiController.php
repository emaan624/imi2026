<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImeiBulkOrder;
use App\Models\ImeiOrder;
use App\Models\ImeiProvider;
use App\Models\ImeiProviderBalance;
use App\Models\ImeiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminImeiController extends Controller
{
    public function analytics(): JsonResponse
    {
        $revenue = (float) ImeiOrder::sum('price');
        $cost = (float) ImeiOrder::sum('provider_cost');
        $refunded = (float) ImeiOrder::sum('refunded_amount');

        return response()->json([
            'profit_analytics' => [
                'gross_profit' => round($revenue - $cost - $refunded, 2),
                'profit_margin_percent' => $revenue > 0 ? round((($revenue - $cost - $refunded) / $revenue) * 100, 2) : 0,
            ],
            'revenue_analytics' => [
                'total_revenue' => round($revenue, 2),
                'total_cost' => round($cost, 2),
                'refunded_amount' => round($refunded, 2),
            ],
            'api_statistics' => [
                'providers_total' => ImeiProvider::count(),
                'services_total' => ImeiService::count(),
                'orders_total' => ImeiOrder::count(),
                'bulk_orders_total' => ImeiBulkOrder::count(),
            ],
            'failed_order_management' => [
                'failed_orders' => ImeiOrder::where('status', 'failed')->count(),
                'pending_orders' => ImeiOrder::whereIn('status', ['pending_submission', 'submitted', 'processing'])->count(),
            ],
            'refund_management' => [
                'refunded_orders' => ImeiOrder::where('status', 'refunded')->count(),
                'refunded_total' => ImeiOrder::sum('refunded_amount'),
            ],
            'provider_balance_dashboard' => ImeiProviderBalance::with('provider:id,name,slug')
                ->latest('last_synced_at')
                ->get(),
        ]);
    }

    public function providersIndex(): JsonResponse
    {
        return response()->json(ImeiProvider::with('balance')->orderBy('priority')->paginate(25));
    }

    public function providersStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:imei_providers,slug'],
            'type' => ['required', 'in:unlockbase,dhru_fusion,generic_rest,generic_xml'],
            'priority' => ['required', 'integer', 'min:1'],
            'api_url' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'supports_webhooks' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['nullable', 'array'],
        ]);

        $provider = ImeiProvider::create($validated);

        return response()->json($provider, 201);
    }

    public function providersUpdate(Request $request, ImeiProvider $imeiProvider): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:imei_providers,slug,'.$imeiProvider->id],
            'type' => ['sometimes', 'in:unlockbase,dhru_fusion,generic_rest,generic_xml'],
            'priority' => ['sometimes', 'integer', 'min:1'],
            'api_url' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'supports_webhooks' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['nullable', 'array'],
        ]);

        $imeiProvider->update($validated);

        return response()->json($imeiProvider->fresh('balance'));
    }

    public function providersDestroy(ImeiProvider $imeiProvider): JsonResponse
    {
        $imeiProvider->delete();

        return response()->json(['message' => 'Provider deleted']);
    }

    public function servicesIndex(): JsonResponse
    {
        return response()->json(ImeiService::with('category:id,name,slug')->latest()->paginate(25));
    }

    public function servicesStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'imei_category_id' => ['required', 'exists:imei_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:imei_services,slug'],
            'checker_type' => ['nullable', 'in:fmi,carrier,blacklist,warranty,network,device_info'],
            'price' => ['required', 'numeric', 'min:0'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'meta' => ['nullable', 'array'],
        ]);

        $service = ImeiService::create($validated);

        return response()->json($service->load('category:id,name,slug'), 201);
    }

    public function servicesUpdate(Request $request, ImeiService $imeiService): JsonResponse
    {
        $validated = $request->validate([
            'imei_category_id' => ['sometimes', 'exists:imei_categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:imei_services,slug,'.$imeiService->id],
            'checker_type' => ['nullable', 'in:fmi,carrier,blacklist,warranty,network,device_info'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'meta' => ['nullable', 'array'],
        ]);

        $imeiService->update($validated);

        return response()->json($imeiService->fresh('category:id,name,slug'));
    }

    public function servicesDestroy(ImeiService $imeiService): JsonResponse
    {
        $imeiService->delete();

        return response()->json(['message' => 'Service deleted']);
    }

    public function ordersIndex(): JsonResponse
    {
        return response()->json(
            ImeiOrder::with(['user:id,name,email', 'service:id,name,slug', 'provider:id,name,slug'])
                ->latest()
                ->paginate(25)
        );
    }

    public function orderShow(ImeiOrder $imeiOrder): JsonResponse
    {
        return response()->json($imeiOrder->load([
            'user:id,name,email',
            'service:id,name,slug',
            'provider:id,name,slug',
            'logs.user:id,name,email',
        ]));
    }

    public function markRefund(Request $request, ImeiOrder $imeiOrder): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $fromStatus = $imeiOrder->status;

        $imeiOrder->update([
            'status' => 'refunded',
            'refunded_amount' => $validated['amount'],
        ]);

        $imeiOrder->logs()->create([
            'user_id' => $request->user()->id,
            'action' => 'refund_marked',
            'from_status' => $fromStatus,
            'to_status' => 'refunded',
            'notes' => $validated['notes'] ?? null,
            'meta' => ['amount' => $validated['amount']],
        ]);

        return response()->json($imeiOrder->fresh());
    }
}
