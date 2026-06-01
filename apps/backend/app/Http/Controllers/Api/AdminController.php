<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'users_total' => User::count(),
            'wallet_volume' => Transaction::sum('amount'),
            'tickets_open' => SupportTicket::where('status', 'open')->count(),
            'transactions_today' => Transaction::whereDate('created_at', now()->toDateString())->count(),
        ]);
    }
}
