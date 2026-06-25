<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['table', 'user', 'items.menuItem'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->get();

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function show(Order $order)
    {
        return response()->json([
            'data' => $order->load(['table', 'user', 'items.menuItem']),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_id'              => 'nullable|exists:tables,id',
            'type'                  => 'required|in:dine_in,takeaway',
            'items'                 => 'required|array|min:1',
            'items.*.menu_item_id'  => 'required|exists:menu_items,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.note'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($request) {
                $totalPrice = 0;
                $orderItems = [];

                foreach ($request->items as $item) {
                    $menuItem = MenuItem::findOrFail($item['menu_item_id']);

                    if (! $menuItem->is_available) {
                        throw new \Exception("Menu '{$menuItem->name}' sedang tidak tersedia.");
                    }

                    $subtotal     = $menuItem->price * $item['quantity'];
                    $totalPrice  += $subtotal;

                    $orderItems[] = [
                        'menu_item_id' => $menuItem->id,
                        'quantity'     => $item['quantity'],
                        'unit_price'   => $menuItem->price,
                        'note'         => $item['note'] ?? null,
                    ];
                }

                $order = Order::create([
                    'table_id'    => $request->table_id,
                    'user_id'     => $request->user()->id,
                    'order_code'  => 'ORD-' . strtoupper(Str::random(8)),
                    'status'      => 'pending',
                    'type'        => $request->type,
                    'total_price' => $totalPrice,
                ]);

                $order->items()->createMany($orderItems);

                return $order;
            });

            return response()->json([
                'message' => 'Pesanan berhasil dibuat',
                'data'    => $order->load(['table', 'items.menuItem']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status pesanan diperbarui',
            'data'    => $order->load(['table', 'items.menuItem']),
        ]);
    }

    public function destroy(Order $order)
    {
        if (! in_array($order->status, ['pending', 'cancelled'])) {
            return response()->json([
                'message' => 'Hanya pesanan berstatus pending atau cancelled yang bisa dihapus.',
            ], 422);
        }

        $order->delete();

        return response()->json([
            'message' => 'Pesanan berhasil dihapus',
        ]);
    }
}