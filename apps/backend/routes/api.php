<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminImeiController;
use App\Http\Controllers\Api\AdminInstallmentController;
use App\Http\Controllers\Api\AdminPtaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImeiController;
use App\Http\Controllers\Api\ImeiWebhookController;
use App\Http\Controllers\Api\InstallmentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PtaController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('wallet', [WalletController::class, 'show']);
    Route::get('wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('wallet/deposit', [WalletController::class, 'deposit']);
    Route::post('wallet/withdraw', [WalletController::class, 'withdraw']);
    Route::post('wallet/transfer', [WalletController::class, 'transfer']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

    Route::apiResource('tickets', SupportTicketController::class);

    Route::get('pta/services', [PtaController::class, 'services']);
    Route::post('pta/calculate-tax', [PtaController::class, 'calculator']);
    Route::get('pta/validate-imei', [PtaController::class, 'validateImei']);
    Route::get('pta/check-eligibility', [PtaController::class, 'eligibility']);
    Route::get('pta/orders', [PtaController::class, 'index']);
    Route::post('pta/orders', [PtaController::class, 'placeOrder']);
    Route::get('pta/orders/{ptaOrder}', [PtaController::class, 'show']);
    Route::get('pta/orders/{ptaOrder}/status', [PtaController::class, 'status']);
    Route::get('pta/orders/{ptaOrder}/history', [PtaController::class, 'history']);
    Route::post('pta/orders/{ptaOrder}/pay', [PtaController::class, 'pay']);
    Route::get('pta/orders/{ptaOrder}/invoice', [PtaController::class, 'invoice']);
    Route::get('pta/orders/{ptaOrder}/receipt', [PtaController::class, 'receipt']);
    Route::post('pta/orders/{ptaOrder}/register/passport', [PtaController::class, 'registerByPassport']);
    Route::post('pta/orders/{ptaOrder}/register/cnic', [PtaController::class, 'registerByCnic']);
    Route::post('pta/orders/{ptaOrder}/register/overseas', [PtaController::class, 'registerOverseas']);

    Route::get('installments/plans', [InstallmentController::class, 'plans']);
    Route::post('installments/plans/{installmentPlan}/contracts', [InstallmentController::class, 'createContract']);
    Route::get('installments/contracts', [InstallmentController::class, 'contracts']);
    Route::get('installments/contracts/{installmentContract}', [InstallmentController::class, 'show']);
    Route::get('installments/contracts/{installmentContract}/schedule', [InstallmentController::class, 'schedule']);
    Route::get('installments/contracts/{installmentContract}/history', [InstallmentController::class, 'history']);
    Route::get('installments/contracts/{installmentContract}/remaining-balance', [InstallmentController::class, 'remainingBalance']);
    Route::get('installments/contracts/{installmentContract}/statement', [InstallmentController::class, 'statement']);
    Route::get('installments/contracts/{installmentContract}/agreement', [InstallmentController::class, 'agreement']);
    Route::post('installments/contracts/{installmentContract}/pay', [InstallmentController::class, 'pay']);
    Route::post('installments/contracts/{installmentContract}/settle-early', [InstallmentController::class, 'settleEarly']);

    Route::get('imei/services', [ImeiController::class, 'services']);
    Route::get('imei/orders', [ImeiController::class, 'orders']);
    Route::get('imei/orders/{imeiOrder}', [ImeiController::class, 'show']);
    Route::get('imei/orders/{imeiOrder}/history', [ImeiController::class, 'history']);
    Route::post('imei/orders', [ImeiController::class, 'placeOrder']);
    Route::get('imei/bulk-orders', [ImeiController::class, 'bulkOrders']);
    Route::post('imei/bulk-orders', [ImeiController::class, 'bulkUpload']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('dashboard', [AdminController::class, 'dashboard']);
    Route::apiResource('users', UserManagementController::class)->except(['create', 'edit']);

    Route::get('pta/dashboard', [AdminPtaController::class, 'dashboard']);
    Route::get('pta/revenue-report', [AdminPtaController::class, 'revenueReport']);
    Route::get('pta/orders', [AdminPtaController::class, 'ordersIndex']);
    Route::get('pta/orders/{ptaOrder}', [AdminPtaController::class, 'orderShow']);
    Route::get('pta/services', [AdminPtaController::class, 'servicesIndex']);
    Route::post('pta/services', [AdminPtaController::class, 'servicesStore']);
    Route::put('pta/services/{ptaService}', [AdminPtaController::class, 'servicesUpdate']);
    Route::delete('pta/services/{ptaService}', [AdminPtaController::class, 'servicesDestroy']);
    Route::post('pta/orders/{ptaOrder}/status', [AdminPtaController::class, 'updateOrderStatus']);

    Route::get('installments/dashboard', [AdminInstallmentController::class, 'dashboard']);
    Route::get('installments/analytics', [AdminInstallmentController::class, 'analytics']);
    Route::get('installments/contracts', [AdminInstallmentController::class, 'contractsIndex']);
    Route::get('installments/contracts/{installmentContract}', [AdminInstallmentController::class, 'contractShow']);
    Route::get('installments/plans', [AdminInstallmentController::class, 'plansIndex']);
    Route::post('installments/plans', [AdminInstallmentController::class, 'plansStore']);
    Route::put('installments/plans/{installmentPlan}', [AdminInstallmentController::class, 'plansUpdate']);
    Route::delete('installments/plans/{installmentPlan}', [AdminInstallmentController::class, 'plansDestroy']);
    Route::post('installments/contracts/{installmentContract}/approve', [AdminInstallmentController::class, 'approve']);
    Route::post('installments/contracts/{installmentContract}/mark-defaulter', [AdminInstallmentController::class, 'markDefaulter']);
    Route::post('installments/contracts/{installmentContract}/recovery-note', [AdminInstallmentController::class, 'recoveryNote']);
    Route::post('installments/contracts/{installmentContract}/adjustment', [AdminInstallmentController::class, 'adjust']);
    Route::post('installments/contracts/{installmentContract}/reminder', [AdminInstallmentController::class, 'triggerReminder']);

    Route::get('imei/analytics', [AdminImeiController::class, 'analytics']);
    Route::get('imei/providers', [AdminImeiController::class, 'providersIndex']);
    Route::post('imei/providers', [AdminImeiController::class, 'providersStore']);
    Route::put('imei/providers/{imeiProvider}', [AdminImeiController::class, 'providersUpdate']);
    Route::delete('imei/providers/{imeiProvider}', [AdminImeiController::class, 'providersDestroy']);
    Route::get('imei/services', [AdminImeiController::class, 'servicesIndex']);
    Route::post('imei/services', [AdminImeiController::class, 'servicesStore']);
    Route::put('imei/services/{imeiService}', [AdminImeiController::class, 'servicesUpdate']);
    Route::delete('imei/services/{imeiService}', [AdminImeiController::class, 'servicesDestroy']);
    Route::get('imei/orders', [AdminImeiController::class, 'ordersIndex']);
    Route::get('imei/orders/{imeiOrder}', [AdminImeiController::class, 'orderShow']);
    Route::post('imei/orders/{imeiOrder}/refund', [AdminImeiController::class, 'markRefund']);
});

Route::post('imei/webhooks/{provider:slug}', [ImeiWebhookController::class, 'handle']);
