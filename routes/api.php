<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\InventoryController;   // INI YANG BELUM ADA!!!
use App\Http\Controllers\Api\CashController;   // INI YANG BELUM ADA!!!
use App\Http\Controllers\Api\OwnerDashboardController; // TAMBAHIN INI!!!
use Illuminate\Support\Facades\Route;

// ==================== AUTH (semua role boleh) ====================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

// ==================== FITUR KASIR (admin & kasir) ====================
Route::middleware(['auth:sanctum', 'role:admin,kasir'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']); // Tambah Produk Baru
    Route::post('/customers/search-or-create', [CustomerController::class, 'searchOrCreate']);
    Route::post('/orders/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders/history', [OrderController::class, 'history']);
    Route::get('/orders/{order_number}', [OrderController::class, 'show']);
    Route::post('/orders/{order_number}/complete', [OrderController::class, 'completeOrderManual']);
    Route::get('/dashboard', [OrderController::class, 'dashboard']);
    Route::post('/orders/{order_number}/cancel', [OrderController::class, 'cancelOrder']);
    Route::post('/orders/{order_number}/ready', [OrderController::class, 'markAsReady']);
    // INI YANG BARU — DASHBOARD PEMASUKAN TUNAI/QRIS/TRANSFER
    Route::get('/dashboard/qtt', [OrderController::class, 'dashboard_qtt']);
    
    // Bonus: kalau mau pake nama pendek
    Route::get('/pemasukan', [OrderController::class, 'dashboard_qtt']);

    // INVENTORY ROUTES (sekarang controller-nya sudah di-import!)
    Route::get('/inventory/opname-list', [InventoryController::class, 'listForOpname']);
    Route::post('/inventory/opname', [InventoryController::class, 'opname']);
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust']); // API Adjust Stok (Restock/Damage)

    // Di dalam grup middleware auth:sanctum + role:admin,kasir,owner
    Route::prefix('cash')->group(function () {
        Route::post('/transaction', [CashController::class, 'store']);     // modal, beli plastik
        Route::post('/deposit-to-main', [CashController::class, 'depositToMain']);
        Route::get('/dashboard', [CashController::class, 'dashboard']);    // owner lihat
        Route::get('/list', [CashController::class, 'listCashiers']);      // <--- INI ROUTE BARU (/api/cash/list) tapi di app urlnya /kasir/list.
    });
    
    // TAMBAH RUTE KHUSUS /api/kasir/list AGAR SESUAI DENGAN API SERVICE FLUTTER
    Route::get('/kasir/list', [CashController::class, 'listCashiers']);


});

// ==================== FITUR KURIR (admin & kurir) ====================
Route::middleware(['auth:sanctum', 'role:admin,kurir'])->group(function () {
    Route::prefix('courier')->group(function () {
        Route::get('/orders', [CourierController::class, 'index']);
        Route::get('/orders/{order_number}', [CourierController::class, 'show']);
        Route::post('/orders/{order_number}/pickup', [CourierController::class, 'pickup']);
        Route::post('/orders/{order_number}/complete', [CourierController::class, 'complete']);
    });
});

// Di routes/api.php — TAMBAHIN INI DI BAWAH
Route::middleware(['auth:sanctum', 'role:owner,admin'])->group(function () {
    Route::get('/owner/dashboard', [OwnerDashboardController::class, 'index']);
    Route::get('/owner/transactions', [OwnerDashboardController::class, 'transactions'])
    ; // RIWAYAT LENGKAP
    Route::get('/owner/inventory', [OwnerDashboardController::class, 'inventory']); // BARU!

});


Route::get('/test-checkout-code', action: function () {
    return "KODE SUDAH UPDATE - " . now();
});