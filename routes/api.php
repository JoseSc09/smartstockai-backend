<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('products', ProductController::class);
    Route::get('products/{product}/predict', [ProductController::class, 'predictStock']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('movements', MovementController::class);
    Route::apiResource('orders', OrderController::class);
    Route::post('chatbot', [ChatbotController::class, 'query']);
});
