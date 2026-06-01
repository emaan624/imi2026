<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SubmitImeiOrderJob;
use App\Models\ImeiBulkOrder;
use App\Models\ImeiCategory;
use App\Models\ImeiOrder;
use App\Models\ImeiOrderLog;
use App\Models\ImeiService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImeiController extends Controller
{
    public function services(): JsonResponse
    {
        $categories = ImeiCategory::query()
            ->where('is_active', true)
            ->with(['services' => function ($query): void {
                $query->where('is_active', true)->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'imei_service_id' => ['required', 'exists:imei_services,id'],
            'imei' => ['required', 'digits:15'],
            'meta' => ['nullable', 'array'],
        ]);

        $service = ImeiService::findOrFail($validated['imei_service_id']);

        $order = DB::transaction(function () use ($validated, $request, $service): ImeiOrder {
            $order = ImeiOrder::create([
                'user_id' => $request->user()->id,
                'imei_service_id' => $service->id,
                'order_number' => 'IMEI-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
                'imei' => $validated['imei'],
                'status' => 'pending_submission',
                'price' => $service->price,
                'meta' => $validated['meta'] ?? null,
            ]);

            ImeiOrderLog::create([
                'imei_order_id' => $order->id,
                'user_id' => $request->user()->id,
                'action' => 'order_created',
                'to_status' => 'pending_submission',
                'notes' => 'IMEI order created by user.',
            ]);

            return $order;
        });

        SubmitImeiOrderJob::dispatch($order->id);

        return response()->json($order->load('service'), 201);
    }

    public function orders(Request $request): JsonResponse
    {
        return response()->json(
            ImeiOrder::query()
                ->with(['service:id,name,slug', 'provider:id,name,slug'])
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(20)
        );
    }

    public function show(Request $request, ImeiOrder $imeiOrder): JsonResponse
    {
        $this->authorizeOrder($imeiOrder, $request->user());

        return response()->json($imeiOrder->load(['service', 'provider', 'logs.user:id,name,email']));
    }

    public function history(Request $request, ImeiOrder $imeiOrder): JsonResponse
    {
        $this->authorizeOrder($imeiOrder, $request->user());

        return response()->json(
            $imeiOrder->logs()->with('user:id,name,email')->latest()->get()
        );
    }

    public function bulkUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'imei_service_id' => ['required', 'exists:imei_services,id'],
            'csv' => ['required', 'string'],
        ]);

        $service = ImeiService::findOrFail($validated['imei_service_id']);
        $imeis = collect(preg_split('/\r\n|\r|\n/', trim($validated['csv'])) ?: [])
            ->map(fn (string $line) => trim((string) str_getcsv($line)[0] ?? ''))
            ->filter(fn (string $imei) => preg_match('/^\d{15}$/', $imei) === 1)
            ->values();

        $bulkOrder = DB::transaction(function () use ($request, $imeis, $service): ImeiBulkOrder {
            $bulk = ImeiBulkOrder::create([
                'user_id' => $request->user()->id,
                'bulk_number' => 'BULK-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
                'status' => $imeis->isEmpty() ? 'failed' : 'pending',
                'total_orders' => $imeis->count(),
            ]);

            foreach ($imeis as $imei) {
                $order = ImeiOrder::create([
                    'user_id' => $request->user()->id,
                    'imei_service_id' => $service->id,
                    'imei_bulk_order_id' => $bulk->id,
                    'order_number' => 'IMEI-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
                    'imei' => $imei,
                    'status' => 'pending_submission',
                    'price' => $service->price,
                ]);

                ImeiOrderLog::create([
                    'imei_order_id' => $order->id,
                    'user_id' => $request->user()->id,
                    'action' => 'bulk_order_item_created',
                    'to_status' => 'pending_submission',
                    'notes' => 'Created from bulk CSV upload.',
                    'meta' => ['bulk_id' => $bulk->id],
                ]);

                SubmitImeiOrderJob::dispatch($order->id);
            }

            return $bulk;
        });

        return response()->json($bulkOrder->load('orders.service'), 201);
    }

    public function bulkOrders(Request $request): JsonResponse
    {
        return response()->json(
            ImeiBulkOrder::query()
                ->withCount('orders')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(20)
        );
    }

    private function authorizeOrder(ImeiOrder $order, User $user): void
    {
        if ($user->role !== 'admin' && $order->user_id !== $user->id) {
            abort(403);
        }
    }
}
