<?php

use Illuminate\Support\Facades\Route;
// routes/web.php
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\OwnerController;

Route::prefix('owner')->name('owner.')->group(function () {

    Route::get('/login', [OwnerController::class, 'login'])->name('login');
    Route::post('/login', [OwnerController::class, 'doLogin'])->name('doLogin');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [OwnerController::class, 'dashboard'])->name('dashboard');
        Route::get('/logout', [OwnerController::class, 'logout'])->name('logout');

        // BARIS INI WAJIB ADA AGAR STATUS BISA DIUBAH!
        Route::post('/order/{order}/status', [OwnerController::class, 'updateStatus'])
            ->name('order.updateStatus');
    });
});
Route::get('/', [CustomerOrderController::class, 'home'])->name('customer.home');
Route::post('/add-to-cart', [CustomerOrderController::class, 'addToCart'])->name('cart.add');
Route::get('/cart', [CustomerOrderController::class, 'cart'])->name('cart.show');
Route::post('/update-cart', [CustomerOrderController::class, 'updateCart'])->name('cart.update');
Route::post('/remove-from-cart/{id}', [CustomerOrderController::class, 'removeFromCart'])->name('cart.remove');
Route::get('/checkout', [CustomerOrderController::class, 'checkout'])->name('checkout');
Route::post('/submit-order', [CustomerOrderController::class, 'submitOrder'])->name('order.submit');
Route::get('/success/{order_number}', [CustomerOrderController::class, 'success'])->name('order.success');
// Tracking pesanan buat customer
Route::get('/track', [CustomerOrderController::class, 'trackForm'])->name('track.form');
Route::post('/track', [CustomerOrderController::class, 'trackOrder'])->name('track.order');