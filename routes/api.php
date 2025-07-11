<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ChatbotController;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::get('products/{product}/predict', [ProductController::class, 'predictStock'])->name('products.predict');
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('movements', MovementController::class);
    Route::apiResource('orders', OrderController::class);
    Route::post('chatbot', [ChatbotController::class, 'query']);
});
