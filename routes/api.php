<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\TableController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/menu-items', [MenuItemController::class, 'index']);

Route::get('/tables/scan/{qrCode}', [TableController::class, 'scan']);
Route::get('/tables/{table}/qr-image', [TableController::class, 'qrImage']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/dashboard', function () {
        $today = now()->toDateString();

        $totalOrdersToday = \App\Models\Order::whereDate('created_at', $today)->count();
        $revenueToday = \App\Models\Order::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('total_price');
        $pendingOrders = \App\Models\Order::where('status', 'pending')->count();
        $totalMenuItems = \App\Models\MenuItem::count();

        $topMenus = \App\Models\OrderItem::selectRaw('menu_item_id, SUM(quantity) as total_qty')
            ->groupBy('menu_item_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->with('menuItem')
            ->get();

        return response()->json([
            'data' => [
                'total_orders_today' => $totalOrdersToday,
                'revenue_today' => $revenueToday,
                'pending_orders' => $pendingOrders,
                'total_menu_items' => $totalMenuItems,
                'top_menus' => $topMenus,
            ],
        ]);
    });

    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/menu-items', [MenuItemController::class, 'store']);
    Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
    Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);

    Route::get('/tables', [TableController::class, 'index']);
    Route::post('/tables', [TableController::class, 'store']);
    Route::put('/tables/{table}', [TableController::class, 'update']);
    Route::delete('/tables/{table}', [TableController::class, 'destroy']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
});