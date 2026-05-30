<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PtaOrder;
use App\Models\PtaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPtaController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'services_total' => PtaService::count(),
            'orders_total' => PtaOrder::count(),
            'orders_pending' => PtaOrder::whereIn('status', ['pending_payment', 'in_review'])->count(),
            'orders_approved' => PtaOrder::where('status', 'approved')->count(),
            'pta_revenue' => PtaOrder::sum('paid_amount'),
        ]);
    }

    public function revenueReport(): JsonResponse
    {
        $daily = PtaOrder::selectRaw('DATE(created_at) as date, SUM(paid_amount) as total')
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(30)
            ->get();

        return response()->json([
            'summary' => [
                'total_revenue' => PtaOrder::sum('paid_amount'),
                'total_orders' => PtaOrder::count(),
            ],
            'daily' => $daily,
        ]);
    }

    public function servicesIndex(): JsonResponse
    {
        return response()->json(PtaService::latest()->paginate(20));
    }

    public function servicesStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:pta_services,slug'],
            'description' => ['nullable', 'string'],
            'base_fee' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $service = PtaService::create($validated);

        return response()->json($service, 201);
    }

    public function servicesUpdate(Request $request, PtaService $ptaService): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:pta_services,slug,'.$ptaService->id],
            'description' => ['nullable', 'string'],
            'base_fee' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $ptaService->update($validated);

        return response()->json($ptaService->fresh());
    }

    public function servicesDestroy(PtaService $ptaService): JsonResponse
    {
        $ptaService->delete();

        return response()->json(['message' => 'Service deleted']);
    }

    public function updateOrderStatus(Request $request, PtaOrder $ptaOrder): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:in_review,approved,rejected'],
            'notes' => ['nullable', 'string'],
        ]);

        $fromStatus = $ptaOrder->status;

        $ptaOrder->status = $validated['status'];
        $ptaOrder->approved_at = $validated['status'] === 'approved' ? now() : null;
        $ptaOrder->save();

        $ptaOrder->logs()->create([
            'user_id' => $request->user()->id,
            'action' => 'admin_status_update',
            'from_status' => $fromStatus,
            'to_status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json($ptaOrder->fresh());
    }
}
