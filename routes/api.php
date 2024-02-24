<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function () {
    return 'welcome to service';
});

Route::prefix('user')->group(function () {
    Route::post('/login', [UserController::class, 'login'])->name('user.login');
    Route::post('/register', [UserController::class, 'register'])->name('user.register');
    Route::post('/register/verify', [UserController::class, 'verifyRegister'])->name('user.verify.register');
});

Route::prefix('merchant')->group(function () {
    Route::post('/login', [MerchantController::class, 'login'])->name('merchant.login');
    Route::post('/register', [MerchantController::class, 'register'])->name('merchant.register');
    Route::post('/register/verify', [MerchantController::class, 'verifyRegister'])->name('merchant.verify.register');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::put('/merchants/{id}/unsuspend', [AdminController::class, 'unsuspendMerchant'])->name('admin.merchants.unsuspend');
    });

    Route::prefix('merchant')->group(function () {
        Route::post('/list', [MerchantController::class, 'list'])->name('merchant.list');
        Route::post('/orders', [MerchantController::class, 'orders'])->name('merchant.orders');
    });

    Route::prefix('order')->group(function () {
        Route::post('/histories', [OrderController::class, 'orderHistories'])->name('order.histories');
        Route::get('/{id}', [OrderController::class, 'show'])->name('order.show');
        Route::post('/book', [OrderController::class, 'bookService'])->name('order.book');
        Route::put('/{id}/accept', [OrderController::class, 'acceptOrder'])->name('order.accept');
        Route::put('/{id}/cancel', [OrderController::class, 'cancelOrder'])->name('order.cancel');
    });
});
