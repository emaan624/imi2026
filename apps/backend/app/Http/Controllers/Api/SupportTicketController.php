<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::query()->latest();

        if ($request->user()->role !== 'admin') {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'priority' => ['nullable', 'in:low,medium,high'],
        ]);

        $ticket = SupportTicket::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'status' => 'open',
            'priority' => $validated['priority'] ?? 'medium',
        ]);

        return response()->json($ticket, 201);
    }

    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorizeTicket($request, $ticket);

        return response()->json($ticket);
    }

    public function update(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorizeTicket($request, $ticket);

        $validated = $request->validate([
            'subject' => ['sometimes', 'string', 'max:255'],
            'message' => ['sometimes', 'string'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'status' => ['sometimes', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket->update($validated);

        return response()->json($ticket->fresh());
    }

    public function destroy(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorizeTicket($request, $ticket);
        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted']);
    }

    private function authorizeTicket(Request $request, SupportTicket $ticket): void
    {
        abort_if($request->user()->role !== 'admin' && $ticket->user_id !== $request->user()->id, 403);
    }
}
