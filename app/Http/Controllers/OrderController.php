<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Notifications\LowStockNotification;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('products')->where('user_id', Auth::id())->get();
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $total = 0;
        $products = [];

        foreach ($request->products as $item) {
            $product = Product::find($item['id']);
            if ($product->stock < $item['quantity']) {
                return response()->json(['error' => "Stock insuficiente para {$product->name}"], 422);
            }
            $total += $product->price * $item['quantity'];
            $products[$product->id] = ['quantity' => $item['quantity'], 'price' => $product->price];
        }

        $order = Order::create([
            'user_id' => Auth::id(),
            'total' => $total,
            'status' => 'pending',
        ]);

        $order->products()->sync($products);

        foreach ($request->products as $item) {
            $product = Product::find($item['id']);
            $product->stock -= $item['quantity'];
            $product->save();

            if ($product->checkLowStock()) {
                $admin = \App\Models\User::where('role', 'admin')->first();
                if ($admin) {
                    $admin->notify(new LowStockNotification($product));
                }
            }
        }

        return response()->json($order->load('products'), 201);
    }

    public function show(Order $order)
    {
        $this->authorizeOrder($order);
        return response()->json($order->load('products'));
    }

    public function update(Request $request, Order $order)
    {
        $this->authorizeOrder($order);

        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Revertir stock de productos anteriores
        foreach ($order->products as $product) {
            $product->stock += $product->pivot->quantity;
            $product->save();
        }

        $total = 0;
        $products = [];

        foreach ($request->products as $item) {
            $product = Product::find($item['id']);
            if ($product->stock < $item['quantity']) {
                return response()->json(['error' => "Stock insuficiente para {$product->name}"], 422);
            }
            $total += $product->price * $item['quantity'];
            $products[$product->id] = ['quantity' => $item['quantity'], 'price' => $product->price];
        }

        $order->update([
            'total' => $total,
            'status' => $request->status,
        ]);

        $order->products()->sync($products);

        foreach ($request->products as $item) {
            $product = Product::find($item['id']);
            $product->stock -= $item['quantity'];
            $product->save();

            if ($product->checkLowStock()) {
                $admin = \App\Models\User::where('role', 'admin')->first();
                if ($admin) {
                    $admin->notify(new LowStockNotification($product));
                }
            }
        }

        return response()->json($order->load('products'));
    }

    public function destroy(Order $order)
    {
        $this->authorizeOrder($order);

        foreach ($order->products as $product) {
            $product->stock += $product->pivot->quantity;
            $product->save();
        }

        $order->products()->detach();
        $order->delete();
        return response()->json(null, 204);
    }

    protected function authorizeOrder(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }
}
