<?php

use App\Http\Controllers\Api\LaundryApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(\App\Http\Middleware\DeployTestAccess::class)->group(function (): void {
    Route::get('/health', [LaundryApiController::class, 'health']);

    Route::post('/auth/register', [LaundryApiController::class, 'register']);
    Route::post('/auth/login', [LaundryApiController::class, 'login']);

    Route::get('/layanan', [LaundryApiController::class, 'services']);
    Route::post('/layanan', [LaundryApiController::class, 'createService']);
    Route::get('/layanan/{id}', [LaundryApiController::class, 'service']);
    Route::put('/layanan/{id}', [LaundryApiController::class, 'updateService']);
    Route::delete('/layanan/{id}', [LaundryApiController::class, 'deleteService']);

    Route::get('/pelanggan', [LaundryApiController::class, 'customers']);
    Route::get('/pelanggan/{id}', [LaundryApiController::class, 'customer']);
    Route::put('/pelanggan/{id}', [LaundryApiController::class, 'updateCustomer']);
    Route::delete('/pelanggan/{id}', [LaundryApiController::class, 'deleteCustomer']);

    Route::get('/users', [LaundryApiController::class, 'users']);
    Route::post('/users', [LaundryApiController::class, 'createUser']);
    Route::get('/users/{id}', [LaundryApiController::class, 'user']);
    Route::patch('/users/{id}', [LaundryApiController::class, 'updateUser']);
    Route::delete('/users/{id}', [LaundryApiController::class, 'deleteUser']);

    Route::get('/pesanan', [LaundryApiController::class, 'orders']);
    Route::post('/pesanan', [LaundryApiController::class, 'createOrder']);
    Route::get('/pesanan/{id}', [LaundryApiController::class, 'order']);
    Route::patch('/pesanan/{id}/status', [LaundryApiController::class, 'updateOrderStatus']);
    Route::patch('/pesanan/{id}/berat', [LaundryApiController::class, 'confirmOrderWeight']);

    Route::post('/pesanan/{id}/pembayaran', [LaundryApiController::class, 'recordPayment']);
    Route::get('/pesanan/{id}/pembayaran', [LaundryApiController::class, 'payment']);

    Route::get('/notifikasi', [LaundryApiController::class, 'notifications']);
    Route::get('/notifikasi/unread-count', [LaundryApiController::class, 'unreadNotificationCount']);
    Route::post('/notifikasi/{id}/read', [LaundryApiController::class, 'markNotificationRead']);
    Route::post('/notifikasi', [LaundryApiController::class, 'createNotification']);

    Route::get('/laporan/ringkasan', [LaundryApiController::class, 'reportSummary']);
    Route::get('/laporan/total-pendapatan', [LaundryApiController::class, 'totalIncome']);
    Route::get('/laporan/jumlah-pesanan', [LaundryApiController::class, 'orderCount']);
});
